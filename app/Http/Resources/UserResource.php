<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'education_level' => $this->education_level,
            'field_of_study'  => $this->field_of_study,
            'country'         => $this->country,
            'avatar_url'      => $this->avatar_url,
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
