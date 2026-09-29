<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
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
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'first_names' => $this->first_names,
            'paternal_surname' => $this->paternal_surname,
            'maternal_surname' => $this->maternal_surname,
            'phone' => $this->phone,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'address' => $this->address,
            'sex' => $this->sex,
            'is_active' => $this->is_active,
            'student_id' => $this->whenLoaded(
                'student',
                fn() => $this->student?->id
            ),
            'guardian_id' => $this->whenLoaded(
                'guardian',
                fn() => $this->guardian?->id
            ),
        ];
    }
}
