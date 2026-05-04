<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'excerpt'       => $this->excerpt,
            'cover_url'     => $this->cover_url,
            'thumb_url'     => $this->thumb_url,
            'post_category' => $this->post_category,
            'author_name'   => $this->author_name,
            'is_featured'   => $this->is_featured,
            'published_at'  => $this->published_at?->toISOString(),
            'views_count'   => $this->views_count,
        ];
    }
}
