<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistantAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'employee_id' => $this->employee_id,
            'grade_section_id' => $this->grade_section_id,

            'start_date' =>
                $this->start_date?->format('Y-m-d'),

            'end_date' =>
                $this->end_date?->format('Y-m-d'),

            'is_active' => (bool) $this->is_active,

            'employee' => new EmployeeResource(
                $this->whenLoaded('employee')
            ),

            'grade_section' => new GradeSectionResource(
                $this->whenLoaded('gradeSection')
            ),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}
