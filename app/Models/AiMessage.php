<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single turn inside an AI conversation.
 *
 * `attachments` is a denormalised snapshot of the files the user sent with
 * the message so old conversations still render correctly even if the
 * underlying upload is later removed.
 */
class AiMessage extends Model
{
    use HasFactory;

    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';

    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'attachments',
        'tokens',
    ];

    protected $casts = [
        'attachments' => 'array',
        'tokens'      => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }
}
