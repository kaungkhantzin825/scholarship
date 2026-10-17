<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One AI chat thread belonging to a user.
 *
 * Messages are stored so the assistant can recall past conversations and
 * so the app can show a history list. Each conversation is scoped to a
 * single user — there is no cross-user sharing.
 */
class AiConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'mode',
        'message_count',
        'total_tokens',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'message_count'   => 'integer',
        'total_tokens'    => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }

    /** Keep the thread title short and readable in the history drawer. */
    public function deriveTitleFrom(string $text): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($clean === '') {
            return 'New conversation';
        }

        return mb_strlen($clean) > 60 ? mb_substr($clean, 0, 57).'…' : $clean;
    }
}
