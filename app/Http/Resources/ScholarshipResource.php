<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight Scholarship resource for lists/cards.
 */
class ScholarshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'title'                 => $this->title,
            'slug'                  => $this->slug,
            'excerpt'               => $this->excerpt,
            'banner_url'            => $this->banner_url,
            'thumb_url'             => $this->thumb_url,
            'amount'                => $this->amount,
            'amount_type'           => $this->amount_type,
            'currency'              => $this->currency,
            'country'               => $this->country,
            'level'                 => $this->level,
            'field_of_study'        => $this->field_of_study,
            'deadline'              => $this->deadline?->toDateString(),
            'is_featured'           => $this->is_featured,
            'status'                => $this->status,
            'days_until_deadline'   => $this->days_until_deadline,
            'is_expired'            => $this->is_expired,
            'views_count'           => $this->views_count,
            'applications_count'    => $this->applications_count,
            'category'              => new CategoryResource($this->whenLoaded('category')),
            'tags'                  => TagResource::collection($this->whenLoaded('tags')),
            'created_at'            => $this->created_at?->toISOString(),
        ];
    }
}
