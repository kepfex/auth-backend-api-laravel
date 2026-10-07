<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>
            $this->id,

            'academic_year_id' =>
            $this->academic_year_id,

            'educational_level_id' =>
            $this->educational_level_id,

            'grade_section_id' =>
            $this->grade_section_id,

            'name' =>
            $this->name,

            'valid_from' =>
            $this->valid_from
                ?->format('Y-m-d'),

            'valid_until' =>
            $this->valid_until
                ?->format('Y-m-d'),

            'is_active' =>
            $this->is_active,

            'scope' =>
            $this->grade_section_id
                ? 'classroom'
                : 'level',

            'academic_year' =>
            new AcademicYearResource(
                $this->whenLoaded(
                    'academicYear'
                )
            ),

            'educational_level' =>
            new EducationalLevelResource(
                $this->whenLoaded(
                    'educationalLevel'
                )
            ),

            'grade_section' =>
            $this->when(
                $this->grade_section_id !== null,
                fn() =>
                new GradeSectionResource(
                    $this->whenLoaded(
                        'gradeSection'
                    )
                )
            ),

            'events' =>
            AttendanceScheduleEventResource::collection(
                $this->whenLoaded(
                    'events'
                )
            ),

            'created_at' =>
            $this->created_at?->toISOString(),

            'updated_at' =>
            $this->updated_at?->toISOString(),
        ];
    }
}
