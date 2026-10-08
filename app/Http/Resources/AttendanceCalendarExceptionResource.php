<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceCalendarExceptionResource extends JsonResource
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

            'attendance_schedule_id' =>
            $this->attendance_schedule_id,

            'date' =>
            $this->date
                ?->format('Y-m-d'),

            'type' =>
            $this->type->value,

            'type_label' =>
            $this->type->label(),

            'name' =>
            $this->name,

            'reason' =>
            $this->reason,

            'is_active' =>
            $this->is_active,

            'scope' =>
            $this->grade_section_id !== null
                ? 'classroom'
                : (
                    $this->educational_level_id !== null
                    ? 'level'
                    : 'institution'
                ),

            'override_schedule' =>
            new AttendanceScheduleResource(
                $this->whenLoaded(
                    'overrideSchedule'
                )
            ),

            'created_at' =>
            $this->created_at
                ?->toISOString(),

            'updated_at' =>
            $this->updated_at
                ?->toISOString(),
        ];
    }
}
