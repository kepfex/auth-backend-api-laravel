<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceDayResource extends JsonResource
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

            'enrollment_id' =>
            $this->enrollment_id,

            'attendance_schedule_id' =>
            $this->attendance_schedule_id,

            'date' =>
            $this->date
                ?->format('Y-m-d'),

            'status' =>
            $this->status->value,

            'status_label' =>
            $this->status->label(),

            'observation' =>
            $this->observation,

            'enrollment' =>
            new EnrollmentResource(
                $this->whenLoaded(
                    'enrollment'
                )
            ),

            'schedule' =>
            new AttendanceScheduleResource(
                $this->whenLoaded(
                    'schedule'
                )
            ),

            'marks' =>
            AttendanceMarkResource::collection(
                $this->whenLoaded(
                    'marks'
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
