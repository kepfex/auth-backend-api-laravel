<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'person_id' => $this->person_id,

            'employee_code' => $this->employee_code,

            'is_active' => (bool) $this->is_active,

            'person' => new PersonResource(
                $this->whenLoaded('person')
            ),

            'positions' => $this->whenLoaded(
                'positions',
                fn () => $this->positions->map(
                    fn ($position) => [
                        'id' => $position->id,
                        'code' => $position->code,
                        'name' => $position->name,
                        'is_active' => (bool) $position->is_active,
                    ]
                )
            ),

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
