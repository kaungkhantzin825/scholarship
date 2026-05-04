<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'status_label'    => $this->status_label,
            'status_color'    => $this->status_color,
            'cover_letter'    => $this->cover_letter,
            'notes'           => $this->notes,
            'additional_info' => $this->additional_info,
            'cv_url'          => $this->getFirstMediaUrl('cv'),
            'applied_at'      => $this->applied_at?->toISOString(),
            'scholarship'     => new ScholarshipResource($this->whenLoaded('scholarship')),
        ];
    }
}
