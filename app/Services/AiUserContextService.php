<?php

namespace App\Services;

use App\Models\AiUpload;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the "who am I talking to" briefing that gets injected into every
 * AI turn. This is what makes the assistant feel personal: it knows the
 * user's profile, their applications, and which scholarships are actually
 * open in the app right now.
 *
 * Everything here is read-only and scoped to a single user.
 */
class AiUserContextService
{
    /** Keep the briefing compact — it is prepended to every prompt. */
    private const MAX_SCHOLARSHIPS = 8;
    private const MAX_APPLICATIONS = 6;

    public function build(?User $user): string
    {
        if (! $user) {
            return $this->guestContext();
        }

        $sections = [];

        $sections[] = $this->profileSection($user);
        $sections[] = $this->applicationSection($user);
        $sections[] = $this->scholarshipSection($user);
        $sections[] = $this->documentSection($user);

        return implode("\n\n", array_filter($sections));
    }

    /**
     * Machine-readable summary the app can render as a "profile card"
     * above the chat (completeness nudge etc.).
     */
    public function summary(User $user): array
    {
        $apps = $user->applications();

        $missing = [];
        if (blank($user->education_level)) $missing[] = 'education level';
        if (blank($user->field_of_study))   $missing[] = 'field of study';
        if (blank($user->country))          $missing[] = 'country';

        $docs = AiUpload::where('user_id', $user->id)->count();

        return [
            'name'                => $user->name,
            'education_level'     => $user->education_level,
            'field_of_study'      => $user->field_of_study,
            'country'             => $user->country,
            'profile_complete'    => $missing === [],
            'missing_fields'      => $missing,
            'applications_total'  => (clone $apps)->count(),
            'applications_pending'=> (clone $apps)->where('status', 'pending')->count(),
            'documents_uploaded'  => $docs,
            'has_cv'              => $user->hasMedia('cv'),
        ];
    }

    // ─── Sections ────────────────────────────────────────────────────────────

    private function guestContext(): string
    {
        return <<<'TXT'
## USER CONTEXT
The user is not signed in. You cannot see their profile, applications or documents.
If they ask something that needs personal data (matching scholarships, application status, reviewing their CV), invite them to sign in — it unlocks personalised recommendations and document review.
TXT;
    }

    private function profileSection(User $user): string
    {
        $lines = ['## USER CONTEXT'];
        $lines[] = '- Name: '.$user->name;
        $lines[] = '- Education level: '.($user->education_level ?: 'not provided');
        $lines[] = '- Field of study: '.($user->field_of_study ?: 'not provided');
        $lines[] = '- Country: '.($user->country ?: 'not provided');
        $lines[] = '- Member since: '.optional($user->created_at)->format('M Y');

        $missing = [];
        if (blank($user->education_level)) $missing[] = 'education level';
        if (blank($user->field_of_study))   $missing[] = 'field of study';
        if (blank($user->country))          $missing[] = 'country';

        if ($missing !== []) {
            $lines[] = '- Profile gaps: '.implode(', ', $missing)
                .'. Ask about these naturally when they matter for a recommendation.';
        }

        return implode("\n", $lines);
    }

    private function applicationSection(User $user): string
    {
        /** @var Collection $apps */
        $apps = $user->applications()
            ->with('scholarship:id,title,deadline')
            ->orderByDesc('created_at')
            ->limit(self::MAX_APPLICATIONS)
            ->get();

        $total = $user->applications()->count();

        if ($total === 0) {
            return "## THEIR APPLICATIONS\nNo applications yet. Encourage them to apply — you can help prepare the cover letter and documents right here in the chat.";
        }

        $lines = ['## THEIR APPLICATIONS ('.$total.' total)'];

        foreach ($apps as $app) {
            $title = optional($app->scholarship)->title ?? 'Deleted scholarship';
            $deadline = optional(optional($app->scholarship)->deadline)->format('d M Y') ?? 'no deadline';
            $lines[] = sprintf(
                '- "%s" — status: %s, deadline: %s',
                $title,
                $app->status,
                $deadline
            );
        }

        if ($total > $apps->count()) {
            $lines[] = '- …and '.($total - $apps->count()).' more.';
        }

        $lines[] = 'When they ask "how is my application going", answer from this list and suggest the next concrete step.';

        return implode("\n", $lines);
    }

    private function scholarshipSection(User $user): string
    {
        $query = Scholarship::query()
            ->active()
            ->where(function ($q) {
                $q->whereNull('deadline')->orWhere('deadline', '>=', now()->toDateString());
            })
            ->with('category:id,name');

        // Prefer opportunities that match the user's field / country when we
        // know them, but never return an empty list because of a filter miss.
        $matching = (clone $query)
            ->when($user->field_of_study, function ($q) use ($user) {
                $q->where(function ($inner) use ($user) {
                    $inner->where('field_of_study', 'like', '%'.$user->field_of_study.'%')
                        ->orWhere('title', 'like', '%'.$user->field_of_study.'%');
                });
            })
            ->orderBy('deadline')
            ->limit(self::MAX_SCHOLARSHIPS)
            ->get();

        if ($matching->count() < 4) {
            $matching = (clone $query)
                ->orderBy('deadline')
                ->limit(self::MAX_SCHOLARSHIPS)
                ->get();
        }

        if ($matching->isEmpty()) {
            return "## OPEN SCHOLARSHIPS IN THE APP\nNo active listings with an upcoming deadline right now.";
        }

        $lines = ['## OPEN SCHOLARSHIPS IN THE APP (real listings — prefer these over generic advice)'];

        foreach ($matching as $s) {
            $bits = [optional($s->category)->name, $s->level, $s->country];
            $bits = array_filter($bits);

            $lines[] = sprintf(
                '- %s (%s) — deadline: %s%s',
                $s->title,
                implode(' · ', $bits) ?: 'general',
                optional($s->deadline)->format('d M Y') ?? 'rolling',
                $s->amount ? ' · funding: '.$s->currency.' '.number_format((float) $s->amount) : ''
            );
        }

        $lines[] = 'Only name scholarships from this list. If the user wants something not listed, say you do not have that listing and point them to the Explore tab.';

        return implode("\n", $lines);
    }

    private function documentSection(User $user): string
    {
        $docs = AiUpload::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'original_name', 'kind', 'text_extracted', 'created_at']);

        if ($docs->isEmpty()) {
            return "## THEIR UPLOADED DOCUMENTS\nNone yet. They can attach a CV, transcript or essay with the paperclip button — you will be able to read it and give feedback.";
        }

        $lines = ['## THEIR UPLOADED DOCUMENTS'];

        foreach ($docs as $doc) {
            $lines[] = sprintf(
                '- %s (%s, uploaded %s)%s',
                $doc->original_name,
                $doc->kind,
                $doc->created_at->format('d M Y'),
                $doc->text_extracted ? ' — text readable' : ' — text not extractable'
            );
        }

        return implode("\n", $lines);
    }
}
