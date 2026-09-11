<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeSectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'shift'         => $this->shift,
            'capacity'      => $this->capacity,
            'is_active'     => $this->is_active,
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'grade'         => new GradeResource($this->whenLoaded('grade')),
            'section'       => new SectionResource($this->whenLoaded('section')),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
