<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeminiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    /**
     * Quick-pick suggestions the app shows in the empty chat state.
     * Kept here so they live alongside the chat logic and are easy
     * to update without shipping a new app build.
     */
    private const SUGGESTIONS = [
        [
            'icon'  => 'search',
            'title' => 'Find scholarships for me',
            'prompt'=> 'Help me find scholarships that match my profile. Ask me about my country, field of study, and level.',
        ],
        [
            'icon'  => 'edit',
            'title' => 'Help me write a motivation letter',
            'prompt'=> 'I need to write a motivation letter for a scholarship application. Can you give me a strong structure and an opening paragraph template I can adapt?',
        ],
        [
            'icon'  => 'event',
            'title' => 'Deadlines closing soon',
            'prompt'=> 'What kinds of scholarships typically close in the next 1-3 months? Give me a checklist of what to prepare.',
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
        [
            'icon'  => 'description',
            'title' => 'Review my essay',
            'prompt'=> 'I\'m about to paste my scholarship essay. Can you review it for clarity, structure, and impact, and suggest 3 specific improvements?',
        ],
    ];

    public function __construct(private readonly GeminiChatService $chat)
    {
    }

    /**
     * POST /api/ai/chat
     *
     * Body:
     *   - message (string, required)  — The newest user message.
     *   - history (array, optional)   — Prior turns: [{role: "user"|"assistant", content: "..."}]
     *
     * Returns:
     *   - reply (string)              — Assistant's reply.
     *   - tokens (int|null)           — Total tokens used (for analytics).
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'                 => 'required|string|min:1|max:4000',
            'history'                 => 'sometimes|array|max:40',
            'history.*.role'          => 'required_with:history|string|in:user,assistant',
            'history.*.content'       => 'required_with:history|string|max:4000',
        ]);

        $message = trim($validated['message']);
        if ($message === '') {
            return response()->json([
                'error' => 'Message cannot be empty.',
            ], 422);
        }

        $history = $validated['history'] ?? [];
        // Normalize and append the new user turn
        $history[] = ['role' => 'user', 'content' => $message];

        // Trim history to last 20 turns to keep prompts compact and cheap
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        try {
            $result = $this->chat->chat($history);
        } catch (\Throwable $e) {
            Log::warning('AI chat failed', [
                'error' => $e->getMessage(),
                'user_id' => optional($request->user())->id,
            ]);
            return response()->json([
                'error'   => 'AI_CHAT_FAILED',
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'reply'  => $result['reply'],
            'tokens' => $result['tokens'],
        ]);
    }

    /**
     * GET /api/ai/suggestions
     *
     * Returns the static list of suggested prompts the app shows
     * in the empty-state of the chat screen.
     */
    public function suggestions(): JsonResponse
    {
        return response()->json([
            'data' => self::SUGGESTIONS,
        ]);
    }
}
