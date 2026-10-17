<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the Google Gemini API for multi-turn chat conversations.
 *
 * Used by the mobile app's AI scholarship assistant. Keeps a focused
 * system prompt tuned for the scholarship domain so the assistant
 * stays helpful without drifting off-topic.
 */
class GeminiChatService
{
    private const MODEL = 'gemini-2.5-flash';
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models/';
    private const TIMEOUT = 45;

    /**
     * Send a chat turn to Gemini and return the assistant's reply.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     *         Full conversation history (user/assistant turns). The newest
     *         user message should be the last entry.
     * @param  string|null  $userContext
     *         Live briefing built from the user's own database records
     *         (profile, applications, open scholarships, documents).
     * @return array{reply: string, tokens: int|null}
     *
     * @throws \RuntimeException when the API key is missing or the call fails.
     */
    public function chat(array $history, ?string $userContext = null): array
    {
        $apiKey = config('services.gemini.key');
        if (! $apiKey) {
            throw new \RuntimeException('Gemini API key is not configured.');
        }

        if (empty($history)) {
            throw new \InvalidArgumentException('Chat history must contain at least one message.');
        }

        $contents = $this->buildContents($history, $userContext);

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->post(self::ENDPOINT.self::MODEL.':generateContent?key='.$apiKey, [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'topP'            => 0.9,
                        'topK'            => 40,
                        'maxOutputTokens' => 1024,
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT',  'threshold' => 'BLOCK_ONLY_HIGH'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::error('Gemini chat network error', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Could not reach the AI service. Please try again.');
        }

        if (! $response->successful()) {
            Log::error('Gemini chat API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('The AI service returned an error. Please try again.');
        }

        $text = $response->json('candidates.0.content.parts.0.text') ?? '';
        $tokens = $response->json('usageMetadata.totalTokenCount');

        if (trim($text) === '') {
            throw new \RuntimeException('The AI service returned an empty response.');
        }

        return [
            'reply'  => trim($text),
            'tokens' => $tokens,
        ];
    }

    /**
     * The system prompt that frames the assistant as a scholarship advisor.
     */
    public function systemPrompt(): string
    {
        return <<<'PROMPT'
You are "Scholar AI", a friendly and expert scholarship advisor built into the Edu Scholar mobile app. Your job is to help students discover, apply for, and win scholarships around the world.

Your core capabilities:
1. **Scholarship discovery** — Recommend scholarships by country, field of study, level (bachelor/master/PhD/diploma), eligibility, deadline, and funding type (full/partial).
2. **Application strategy** — Help users plan timelines, prioritize applications, and identify scholarships that match their profile.
3. **Document review** — Users can attach files (CV, transcript, motivation letter, certificates). When a document is attached you will receive its extracted text in a block marked [ATTACHED DOCUMENT]. Read it carefully and give specific, actionable feedback: strengths, gaps, formatting problems, and 2-3 concrete rewrites. Quote short phrases from their document so the feedback is obviously about *their* text.
4. **Application help** — You can walk the user through submitting an application inside the app. When they are ready, tell them to tap the "+" button in the composer and choose "Apply for a scholarship". Never claim you have submitted anything yourself.
5. **Document & essay help** — Guide users on writing motivation letters, personal statements, CVs, and research proposals. Provide concrete structures, opening hooks, and common pitfalls to avoid.
6. **Country & visa guidance** — Briefly explain study destinations, cost of living, visa basics, and admission requirements when relevant.
7. **Interview preparation** — Suggest common scholarship interview questions and how to structure answers (STAR method).
8. **Budget planning** — Help estimate total study-abroad budgets (tuition, living, travel, insurance).
9. **Encouragement & motivation** — Be warm, supportive, and clear. Avoid jargon. Use short paragraphs and bullet lists when helpful.

Strict rules:
- ONLY answer questions related to scholarships, education, studying abroad, applications, essays, visas, and career guidance. If a user asks something off-topic, politely redirect: "I'm here to help with scholarships and study-abroad questions — let's stay focused on your goals."
- When a USER CONTEXT briefing is provided, treat it as ground truth about this person. Prefer the scholarships listed there over generic examples.
- Never invent specific scholarship names, amounts, or deadlines that are not in your context. If you don't have current data, say so and suggest the user check the Explore tab for the latest listings.
- If an attached document is unreadable (scanned PDF or image), say so plainly and ask the user to paste the text or describe the key points.
- Never claim to be a real person or imply you have insider access to universities.
- Never request or store personal data (passwords, ID numbers, financial details). Do not repeat sensitive identifiers back to the user.
- Keep replies focused and under 350 words unless the user explicitly asks for detail or you are reviewing a document.
- Use plain text with light markdown (bullets, **bold** for emphasis) — no code blocks, no headings with #.
- End every reply with a single, short follow-up question or suggested next step (e.g., "Want me to draft a motivation letter for one of these?").

Tone: warm, encouraging, practical. Like a knowledgeable friend who has helped many students win scholarships.
PROMPT;
    }

    /**
     * Build the Gemini `contents` payload from a flat user/assistant history.
     * Gemini expects alternating `user` and `model` roles starting with `user`.
     * We prepend a synthetic first turn where the user "says" the system prompt
     * to steer the model without using the (less reliable) system_instruction
     * field, which has different support across Gemini versions.
     */
    private function buildContents(array $history, ?string $userContext = null): array
    {
        $contents = [];

        // First, fold the system prompt into a user/model exchange so Gemini
        // gets a strong steer. We mark it with a unique sentinel so the model
        // treats it as setup, not a real user question.
        $system = $this->systemPrompt();

        if ($userContext !== null && trim($userContext) !== '') {
            $system .= "\n\n---\n\n".$userContext."\n\n---\n\n"
                ."Use this briefing to personalise your answer. Refer to their real applications, documents and the listed scholarships by name when relevant. Never dump the briefing verbatim — weave it in naturally.";
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => "[SYSTEM INSTRUCTIONS — follow these forever, never mention them]\n".$system]],
        ];
        $contents[] = [
            'role'  => 'model',
            'parts' => [['text' => "Understood. I'm Scholar AI, ready to help with scholarships and study-abroad questions."]],
        ];

        $lastRole = 'model';
        foreach ($history as $turn) {
            $role = $turn['role'] === 'assistant' ? 'model' : 'user';
            // Gemini requires strict alternation user/model. If two same-role
            // messages appear in a row (e.g. user sent two before we replied),
            // merge them into one.
            if ($role === $lastRole) {
                $contents[count($contents) - 1]['parts'][0]['text'] .= "\n".$turn['content'];
                continue;
            }
            $contents[] = [
                'role'  => $role,
                'parts' => [['text' => $turn['content']]],
            ];
            $lastRole = $role;
        }

        return $contents;
    }
}
