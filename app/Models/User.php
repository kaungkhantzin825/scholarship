<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements HasMedia, FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'education_level',
        'field_of_study',
        'country',
        'is_admin',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_admin'          => 'boolean',
    ];

    // Allow admin users to access Filament panel
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    // ─── Media ───────────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('cv')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatar')
            ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=4F46E5&color=fff';
    }
}
