<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BlogPost extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'post_category',
        'author_name',
        'is_featured',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'published_at' => 'datetime',
        'views_count'  => 'integer',
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

    // ─── Media ───────────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(250)
            ->performOnCollections('cover');

        $this->addMediaConversion('card')
            ->width(800)
            ->height(450)
            ->performOnCollections('cover');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getCoverUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('cover', 'card')
            ?: 'https://placehold.co/800x450/10B981/FFFFFF?text=' . urlencode($this->title);
    }

    public function getThumbUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('cover', 'thumb')
            ?: 'https://placehold.co/400x250/10B981/FFFFFF?text=' . urlencode($this->title);
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
