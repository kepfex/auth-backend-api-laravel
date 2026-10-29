<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionalUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'email' => $this->email,
            'is_active' => (bool) $this->is_active,
            'person' => $this->whenLoaded('person', fn () => [
                'id' => $this->person->id,
                'document_type' => $this->person->document_type,
                'document_number' => $this->person->document_number,
                'first_names' => $this->person->first_names,
                'paternal_surname' => $this->person->paternal_surname,
                'maternal_surname' => $this->person->maternal_surname,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
