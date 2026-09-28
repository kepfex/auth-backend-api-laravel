<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'student_code' => $this->student_code,
            'status' => $this->status,
            'person' => new PersonResource($this->whenLoaded('person')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
