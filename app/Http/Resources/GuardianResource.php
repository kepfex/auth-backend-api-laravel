<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'occupation' => $this->occupation,
            'is_active' => $this->is_active,

            'person' => new PersonResource(
                $this->whenLoaded('person')
            ),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
