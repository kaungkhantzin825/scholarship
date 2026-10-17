<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A file the user uploaded into the AI chat.
 *
 * We keep the extracted plain text alongside the file so the AI can read
 * documents (CVs, transcripts, essays) without re-parsing on every turn.
 */
class AiUpload extends Model
{
    use HasFactory;

    public const KINDS = ['cv', 'transcript', 'essay', 'certificate', 'document'];

    protected $fillable = [
        'user_id',
        'original_name',
        'path',
        'disk',
        'mime',
        'extension',
        'size',
        'kind',
        'extracted_text',
        'extracted_chars',
        'text_extracted',
    ];

    protected $attributes = [
        'disk' => 'public',
    ];

    protected $casts = [
        'size'            => 'integer',
        'extracted_chars' => 'integer',
        'text_extracted'  => 'boolean',
    ];

    protected $hidden = ['extracted_text', 'path'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Public URL for the app to open the file. */
    public function getUrlAttribute(): ?string
    {
        try {
            return Storage::disk($this->disk)->url($this->path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Shape sent to the mobile app. */
    public function toApiArray(): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->original_name,
            'mime'            => $this->mime,
            'extension'       => $this->extension,
            'size'            => $this->size,
            'kind'            => $this->kind,
            'url'             => $this->url,
            'text_extracted'  => $this->text_extracted,
            'extracted_chars' => $this->extracted_chars,
        ];
    }
}
