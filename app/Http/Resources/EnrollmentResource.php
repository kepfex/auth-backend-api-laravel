<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
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

            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'grade_section_id' => $this->grade_section_id,

            'enrollment_date' => $this->enrollment_date?->format('Y-m-d'),

            'status' => $this->status->value,
            'status_label' => $this->status->label(),

            'observations' => $this->observations,

            'student' => new StudentResource(
                $this->whenLoaded('student')
            ),

            'academic_year' => new AcademicYearResource(
                $this->whenLoaded('academicYear')
            ),

            'grade_section' => new GradeSectionResource(
                $this->whenLoaded('gradeSection')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
