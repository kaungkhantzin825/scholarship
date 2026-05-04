<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Scholarship extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'banner_image',
        'excerpt',
        'description',
        'eligibility',
        'benefits',
        'required_documents',
        'amount',
        'amount_type',
        'currency',
        'country',
        'eligible_countries',
        'level',
        'field_of_study',
        'deadline',
        'start_date',
        'official_link',
        'is_featured',
        'status',
        'views_count',
        'applications_count',
    ];

    protected $casts = [
        'eligible_countries' => 'array',
        'amount'             => 'decimal:2',
        'deadline'           => 'date',
        'start_date'         => 'date',
        'is_featured'        => 'boolean',
        'views_count'        => 'integer',
        'applications_count' => 'integer',
    ];

    // ─── Slug ────────────────────────────────────────────────────────────────

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    // ─── Media ───────────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('gallery');

        $this->addMediaCollection('documents');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(250)
            ->performOnCollections('banner');

        $this->addMediaConversion('card')
            ->width(800)
            ->height(450)
            ->performOnCollections('banner');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, fn($q, $s) =>
            $q->where('title', 'like', "%{$s}%")
              ->orWhere('excerpt', 'like', "%{$s}%")
        );

        $query->when($filters['country'] ?? null, fn($q, $v) =>
            $q->where('country', $v)
        );

        $query->when($filters['level'] ?? null, fn($q, $v) =>
            $q->where('level', $v)
        );

        $query->when($filters['category'] ?? null, fn($q, $v) =>
            $q->whereHas('category', fn($cq) => $cq->where('slug', $v))
        );

        $query->when($filters['field'] ?? null, fn($q, $v) =>
            $q->where('field_of_study', 'like', "%{$v}%")
        );

        $query->when($filters['amount_type'] ?? null, fn($q, $v) =>
            $q->where('amount_type', $v)
        );

        $query->when(isset($filters['deadline_from']), fn($q) =>
            $q->where('deadline', '>=', $filters['deadline_from'])
        );

        $query->when(isset($filters['deadline_to']), fn($q) =>
            $q->where('deadline', '<=', $filters['deadline_to'])
        );

        return $query;
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getBannerUrlAttribute(): string
    {
        $url = $this->banner_image ? url('storage/' . $this->banner_image) : null;
        if (! $url) {
            return 'https://placehold.co/800x450/4F46E5/FFFFFF?text=' . urlencode($this->title);
        }

        if (request()->is('api/*') && app()->environment('local')) {
            $url = str_replace(['localhost', '127.0.0.1'], '10.0.2.2', $url);
        }

        return $url;
    }

    public function getThumbUrlAttribute(): string
    {
        $url = $this->banner_image ? url('storage/' . $this->banner_image) : null;
        if (! $url) {
            return 'https://placehold.co/400x250/4F46E5/FFFFFF?text=' . urlencode($this->title);
        }

        if (request()->is('api/*') && app()->environment('local')) {
            $url = str_replace(['localhost', '127.0.0.1'], '10.0.2.2', $url);
        }

        return $url;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }

    public function getDaysUntilDeadlineAttribute(): ?int
    {
        if (! $this->deadline) return null;
        return max(0, now()->diffInDays($this->deadline, false));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
