<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full Scholarship resource for detail page.
 */
class ScholarshipDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'title'                 => $this->title,
            'slug'                  => $this->slug,
            'excerpt'               => $this->excerpt,
            'description'           => $this->description,
            'eligibility'           => $this->eligibility,
            'benefits'              => $this->benefits,
            'required_documents'    => $this->required_documents,
            'banner_url'            => $this->banner_url,
            'thumb_url'             => $this->thumb_url,
            'gallery'               => $this->getMedia('gallery')->map(fn($m) => [
                'url'  => $m->getUrl(),
                'name' => $m->name,
            ]),
            'amount'                => $this->amount,
            'amount_type'           => $this->amount_type,
            'currency'              => $this->currency,
            'country'               => $this->country,
            'eligible_countries'    => $this->eligible_countries,
            'level'                 => $this->level,
            'field_of_study'        => $this->field_of_study,
            'deadline'              => $this->deadline?->toDateString(),
            'start_date'            => $this->start_date?->toDateString(),
            'official_link'         => $this->official_link,
            'is_featured'           => $this->is_featured,
            'status'                => $this->status,
            'days_until_deadline'   => $this->days_until_deadline,
            'is_expired'            => $this->is_expired,
            'views_count'           => $this->views_count,
            'applications_count'    => $this->applications_count,
            'category'              => new CategoryResource($this->whenLoaded('category')),
            'tags'                  => TagResource::collection($this->whenLoaded('tags')),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
