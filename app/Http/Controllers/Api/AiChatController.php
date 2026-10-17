<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiUpload;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\User;
use App\Services\AiUserContextService;
use App\Services\DocumentTextExtractor;
use App\Services\GeminiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    /** Max characters of an uploaded document we feed into a single prompt. */
    private const MAX_DOC_CHARS = 12000;

    /**
     * Quick-pick suggestions the app shows in the empty chat state.
     * Kept here so they live alongside the chat logic and are easy
     * to update without shipping a new app build.
     */
    private const SUGGESTIONS = [
        [
            'icon'  => 'search',
            'title' => 'Find scholarships for me',
            'prompt'=> 'Help me find scholarships that match my profile. Use what you know about me and ask about anything that is missing.',
        ],
        [
            'icon'  => 'upload',
            'title' => 'Review my CV',
            'prompt'=> 'I am going to attach my CV. Please review it for a scholarship application and tell me what to improve.',
        ],
        [
            'icon'  => 'description',
            'title' => 'Review my essay',
            'prompt'=> 'I am about to paste my scholarship essay. Can you review it for clarity, structure, and impact, and suggest 3 specific improvements?',
        ],
        [
            'icon'  => 'edit',
            'title' => 'Help me write a motivation letter',
            'prompt'=> 'I need to write a motivation letter for a scholarship application. Can you give me a strong structure and an opening paragraph template I can adapt?',
        ],
        [
            'icon'  => 'assignment',
            'title' => 'How is my application going?',
            'prompt'=> 'Can you check the status of my applications and tell me what I should do next?',
        ],
        [
            'icon'  => 'event',
            'title' => 'Deadlines closing soon',
            'prompt'=> 'Which scholarships in the app are closing soon, and what should I prepare for each one?',
        ],
        [
            'icon'  => 'public',
            'title' => 'Study in the UK',
            'prompt'=> 'What are the best fully-funded UK scholarships for international master\'s students? Mention eligibility and what they cover.',
        ],
        [
            'icon'  => 'science',
            'title' => 'STEM opportunities',
            'prompt'=> 'I\'m a STEM student. What are the top scholarships I should look at, and what makes a strong STEM application?',
        ],
        [
            'icon'  => 'quiz',
            'title' => 'Interview prep',
            'prompt'=> 'What are the 8 most common scholarship interview questions, and how should I structure strong answers?',
        ],
        [
            'icon'  => 'savings',
            'title' => 'Budget my studies',
            'prompt'=> 'I\'m planning to study abroad. Can you help me build a realistic monthly budget and list the main expenses I should plan for?',
        ],
    ];

    public function __construct(
        private readonly GeminiChatService $chat,
        private readonly AiUserContextService $context,
        private readonly DocumentTextExtractor $extractor,
    ) {
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  CHAT
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * POST /api/ai/chat
     *
     * Body:
     *   - message        (string, required)   Newest user message.
     *   - history        (array, optional)    Prior turns when there is no server thread.
     *   - conversation_id(int, optional)      Continue an existing thread.
     *   - attachment_ids (array, optional)    AiUpload ids to include in this turn.
     *
     * Returns reply + tokens + the persisted conversation/message ids so the
     * app can resume the same thread later.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'          => 'required|string|min:1|max:8000',
            'history'          => 'sometimes|array|max:40',
            'history.*.role'   => 'required_with:history|string|in:user,assistant',
            'history.*.content'=> 'required_with:history|string|max:8000',
            'conversation_id'  => 'sometimes|nullable|integer',
            'attachment_ids'   => 'sometimes|array|max:5',
            'attachment_ids.*' => 'integer',
        ]);

        $message = trim($validated['message']);
        if ($message === '') {
            return response()->json(['error' => 'Message cannot be empty.'], 422);
        }

        $user = $this->resolveUser($request);

        // ── Resolve attachments (owned by this user only) ────────────────────
        $uploads = $this->resolveUploads($user, $validated['attachment_ids'] ?? []);

        // ── Build the history we send to the model ───────────────────────────
        $history = $this->buildHistory($user, $validated, $message, $uploads);

        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        // ── Call the model ──────────────────────────────────────────────────
        try {
            $result = $this->chat->chat($history, $this->context->build($user));
        } catch (\Throwable $e) {
            Log::warning('AI chat failed', [
                'error'   => $e->getMessage(),
                'user_id' => optional($user)->id,
            ]);

            return response()->json([
                'error'   => 'AI_CHAT_FAILED',
                'message' => $e->getMessage(),
            ], 502);
        }

        // ── Persist the thread (signed-in users only) ───────────────────────
        $conversationId = null;
        $messageId = null;
        $replyId = null;

        if ($user) {
            [$conversationId, $messageId, $replyId] = $this->persistTurn(
                $user,
                $validated['conversation_id'] ?? null,
                $message,
                $uploads,
                $result['reply'],
                $result['tokens'] ?? null
            );
        }

        return response()->json([
            'reply'           => $result['reply'],
            'tokens'          => $result['tokens'],
            'conversation_id' => $conversationId,
            'message_id'      => $messageId,
            'reply_id'        => $replyId,
            'attachments'     => array_map(fn (AiUpload $u) => $u->toApiArray(), $uploads),
        ]);
    }

    /**
     * GET /api/ai/suggestions
     */
    public function suggestions(): JsonResponse
    {
        return response()->json(['data' => self::SUGGESTIONS]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  UPLOADS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * POST /api/ai/upload  (multipart)
     *
     * Field: file, plus optional `kind` (cv|transcript|essay|certificate|document).
     * Stores the file, extracts its text so the AI can read it, and returns
     * the upload record the app sends back as `attachment_ids`.
     */
    public function upload(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,txt,rtf,md,csv,jpg,jpeg,png,webp',
            'kind' => 'sometimes|string|in:'.implode(',', AiUpload::KINDS),
        ]);

        $file = $validated['file'];
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $mime = $file->getClientMimeType();

        $path = $file->storeAs(
            'ai-uploads/'.$user->id,
            Str::uuid()->toString().'.'.$extension,
            'public'
        );

        $absolute = Storage::disk('public')->path($path);
        $extraction = $this->extractor->extract($absolute, $extension, $mime);

        $upload = AiUpload::create([
            'user_id'         => $user->id,
            'original_name'   => $file->getClientOriginalName(),
            'path'            => $path,
            'disk'            => 'public',
            'mime'            => $mime,
            'extension'       => $extension,
            'size'            => $file->getSize() ?: 0,
            'kind'            => $validated['kind'] ?? $this->guessKind($file->getClientOriginalName()),
            'extracted_text'  => $extraction['text'] ?: null,
            'extracted_chars' => mb_strlen($extraction['text'] ?? ''),
            'text_extracted'  => $extraction['ok'],
        ]);

        return response()->json([
            'data' => $upload->toApiArray() + [
                'note'   => $extraction['note'],
                'preview'=> $extraction['ok']
                    ? mb_substr($extraction['text'], 0, 300)
                    : null,
            ],
        ], 201);
    }

    /**
     * GET /api/ai/uploads — the user's document locker.
     */
    public function uploads(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        $items = AiUpload::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $items->map->toApiArray()->values(),
        ]);
    }

    /**
     * DELETE /api/ai/uploads/{id}
     */
    public function deleteUpload(Request $request, int $id): JsonResponse
    {
        $user = $this->requireUser($request);

        $upload = AiUpload::where('user_id', $user->id)->findOrFail($id);

        try {
            Storage::disk($upload->disk)->delete($upload->path);
        } catch (\Throwable $e) {
            Log::info('Could not delete AI upload file', ['id' => $id, 'error' => $e->getMessage()]);
        }

        $upload->delete();

        return response()->json(['message' => 'File removed.']);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  APPLICATIONS (in-chat apply flow)
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * POST /api/ai/apply
     *
     * Submits a scholarship application directly from the chat, optionally
     * attaching documents the user already uploaded into the conversation.
     *
     * Body:
     *   - scholarship_id (int, required)
     *   - cover_letter   (string, optional)
     *   - notes          (string, optional)
     *   - attachment_ids (array, optional)
     */
    public function apply(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        $validated = $request->validate([
            'scholarship_id'  => 'required|exists:scholarships,id',
            'cover_letter'    => 'nullable|string|max:5000',
            'notes'           => 'nullable|string|max:2000',
            'attachment_ids'  => 'sometimes|array|max:5',
            'attachment_ids.*'=> 'integer',
        ]);

        $scholarship = Scholarship::findOrFail($validated['scholarship_id']);

        if ($scholarship->status !== 'active') {
            return response()->json([
                'message' => 'This scholarship is no longer accepting applications.',
            ], 422);
        }

        $exists = Application::where('user_id', $user->id)
            ->where('scholarship_id', $scholarship->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You have already applied for this scholarship.',
                'code'    => 'ALREADY_APPLIED',
            ], 422);
        }

        $uploads = $this->resolveUploads($user, $validated['attachment_ids'] ?? []);

        $application = Application::create([
            'user_id'         => $user->id,
            'scholarship_id'  => $scholarship->id,
            'cover_letter'    => $validated['cover_letter'] ?? null,
            'notes'           => $validated['notes'] ?? null,
            'additional_info' => [
                'source'      => 'ai_chat',
                'attachments' => array_map(fn (AiUpload $u) => [
                    'id'   => $u->id,
                    'name' => $u->original_name,
                    'kind' => $u->kind,
                ], $uploads),
            ],
            'status'     => 'pending',
            'applied_at' => now(),
        ]);

        // Copy the chat uploads into the application's media collections so
        // they are visible to admins in the panel and survive upload cleanup.
        foreach ($uploads as $upload) {
            try {
                $collection = $upload->kind === 'cv' && ! $application->hasMedia('cv')
                    ? 'cv'
                    : 'documents';

                $application
                    ->addMediaFromDisk($upload->path, $upload->disk)
                    ->usingFileName(Str::slug(pathinfo($upload->original_name, PATHINFO_FILENAME))
                        .'.'.($upload->extension ?: 'bin'))
                    ->toMediaCollection($collection);
            } catch (\Throwable $e) {
                Log::warning('Could not attach upload to application', [
                    'upload_id'      => $upload->id,
                    'application_id' => $application->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $scholarship->increment('applications_count');

        return response()->json([
            'message' => 'Application submitted successfully.',
            'data'    => new ApplicationResource($application->load('scholarship.category')),
        ], 201);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  CONTEXT
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/ai/context
     *
     * What the assistant knows about this user, plus the scholarships the
     * app can offer in the "Apply" picker.
     */
    public function contextSummary(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        $open = Scholarship::query()
            ->active()
            ->where(function ($q) {
                $q->whereNull('deadline')->orWhere('deadline', '>=', now()->toDateString());
            })
            ->orderBy('deadline')
            ->limit(40)
            ->get(['id', 'title', 'deadline', 'country', 'level', 'slug']);

        $appliedIds = $user->applications()->pluck('scholarship_id')->all();

        return response()->json([
            'profile'      => $this->context->summary($user),
            'scholarships' => $open->map(fn (Scholarship $s) => [
                'id'          => $s->id,
                'title'       => $s->title,
                'slug'        => $s->slug,
                'deadline'    => optional($s->deadline)->toDateString(),
                'country'     => $s->country,
                'level'       => $s->level,
                'applied'     => in_array($s->id, $appliedIds, true),
            ])->values(),
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  CONVERSATION HISTORY
    // ═════════════════════════════════════════════════════════════════════════

    /** GET /api/ai/conversations */
    public function conversations(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        $items = AiConversation::where('user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->limit(30)
            ->get(['id', 'title', 'message_count', 'last_message_at', 'created_at']);

        return response()->json([
            'data' => $items->map(fn (AiConversation $c) => [
                'id'              => $c->id,
                'title'           => $c->title,
                'message_count'   => $c->message_count,
                'last_message_at' => optional($c->last_message_at)->toIso8601String(),
            ])->values(),
        ]);
    }

    /** GET /api/ai/conversations/{id} */
    public function showConversation(Request $request, int $id): JsonResponse
    {
        $user = $this->requireUser($request);

        $conversation = AiConversation::where('user_id', $user->id)->findOrFail($id);

        $messages = $conversation->messages()
            ->orderBy('id')
            ->limit(200)
            ->get();

        return response()->json([
            'data' => [
                'id'       => $conversation->id,
                'title'    => $conversation->title,
                'messages' => $messages->map(fn (AiMessage $m) => [
                    'id'          => $m->id,
                    'role'        => $m->role,
                    'content'     => $m->content,
                    'attachments' => $m->attachments ?? [],
                    'created_at'  => optional($m->created_at)->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    /** DELETE /api/ai/conversations/{id} */
    public function destroyConversation(Request $request, int $id): JsonResponse
    {
        $user = $this->requireUser($request);

        $conversation = AiConversation::where('user_id', $user->id)->findOrFail($id);
        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted.']);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Helpers
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Resolve the caller from the Sanctum guard without requiring a login.
     * Guests can still chat — they just don't get persistence or personal data.
     */
    private function resolveUser(Request $request): ?User
    {
        return $request->user() ?: auth('sanctum')->user();
    }

    private function requireUser(Request $request): User
    {
        $user = $this->resolveUser($request);

        abort_if(! $user, 401, 'Authentication required.');

        return $user;
    }

    /** @return array<int, AiUpload> */
    private function resolveUploads(?User $user, array $ids): array
    {
        if (! $user || $ids === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        return AiUpload::where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->get()
            ->all();
    }

    /**
     * Compose the model history: stored thread (if any) + prior turns from
     * the client + the new message with its document blocks.
     */
    private function buildHistory(
        ?User $user,
        array $validated,
        string $message,
        array $uploads
    ): array {
        $history = [];

        // Prefer the persisted thread so the model sees the real conversation.
        if ($user && ! empty($validated['conversation_id'])) {
            $conversation = AiConversation::where('user_id', $user->id)
                ->find($validated['conversation_id']);

            if ($conversation) {
                $history = $conversation->messages()
                    ->orderBy('id')
                    ->limit(40)
                    ->get()
                    ->map(fn (AiMessage $m) => [
                        'role'    => $m->role,
                        'content' => (string) $m->content,
                    ])
                    ->all();
            }
        }

        if ($history === [] && ! empty($validated['history'])) {
            $history = array_map(fn ($turn) => [
                'role'    => $turn['role'],
                'content' => $turn['content'],
            ], $validated['history']);
        }

        $history[] = [
            'role'    => 'user',
            'content' => $this->composeUserContent($message, $uploads),
        ];

        return $history;
    }

    /**
     * Append readable document text to the user's message so the model can
     * actually review the file they attached.
     */
    private function composeUserContent(string $message, array $uploads): string
    {
        if ($uploads === []) {
            return $message;
        }

        $blocks = [];

        foreach ($uploads as $upload) {
            /** @var AiUpload $upload */
            $header = sprintf(
                '[ATTACHED DOCUMENT: %s | type: %s | %s]',
                $upload->original_name,
                $upload->kind,
                $upload->text_extracted ? 'text extracted' : 'no readable text'
            );

            if ($upload->text_extracted && filled($upload->extracted_text)) {
                $body = mb_substr($upload->extracted_text, 0, self::MAX_DOC_CHARS);
                if (mb_strlen($upload->extracted_text) > self::MAX_DOC_CHARS) {
                    $body .= "\n[… truncated …]";
                }
            } else {
                $body = '(This file could not be read as text. Ask the user to paste the key parts if you need the details.)';
            }

            $blocks[] = $header."\n".$body."\n[/ATTACHED DOCUMENT]";
        }

        return $message."\n\n".implode("\n\n", $blocks);
    }

    /**
     * Persist the user turn and the assistant reply, creating the thread on
     * first use and titling it from the opening message.
     *
     * @return array{0:int,1:int,2:int} [conversationId, messageId, replyId]
     */
    private function persistTurn(
        User $user,
        ?int $conversationId,
        string $message,
        array $uploads,
        string $reply,
        ?int $tokens
    ): array {
        $conversation = null;

        if ($conversationId) {
            $conversation = AiConversation::where('user_id', $user->id)->find($conversationId);
        }

        if (! $conversation) {
            $conversation = AiConversation::create([
                'user_id'         => $user->id,
                'title'           => (new AiConversation())->deriveTitleFrom($message),
                'mode'            => 'general',
                'message_count'   => 0,
                'last_message_at' => now(),
            ]);
        }

        $attachments = array_map(fn (AiUpload $u) => $u->toApiArray(), $uploads);

        $userMessage = $conversation->messages()->create([
            'role'        => AiMessage::ROLE_USER,
            'content'     => $message,
            'attachments' => $attachments ?: null,
        ]);

        $replyMessage = $conversation->messages()->create([
            'role'    => AiMessage::ROLE_ASSISTANT,
            'content' => $reply,
            'tokens'  => $tokens,
        ]);

        $conversation->increment('message_count', 2);
        $conversation->increment('total_tokens', (int) $tokens);
        $conversation->update(['last_message_at' => now()]);

        return [$conversation->id, $userMessage->id, $replyMessage->id];
    }

    /** Guess a document kind from the filename so the AI gets better hints. */
    private function guessKind(string $name): string
    {
        $n = Str::lower($name);

        return match (true) {
            str_contains($n, 'cv'), str_contains($n, 'resume')       => 'cv',
            str_contains($n, 'transcript'), str_contains($n, 'result')=> 'transcript',
            str_contains($n, 'essay'), str_contains($n, 'statement'),
            str_contains($n, 'motivation')                            => 'essay',
            str_contains($n, 'cert'), str_contains($n, 'award')       => 'certificate',
            default                                                   => 'document',
        };
    }
}
