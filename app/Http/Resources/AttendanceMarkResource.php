<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceMarkResource extends JsonResource
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

            'attendance_day_id' =>
            $this->attendance_day_id,

            'attendance_schedule_event_id' =>
            $this->attendance_schedule_event_id,

            'event_type' =>
            $this->event_type->value,

            'event_type_label' =>
            $this->event_type->label(),

            'recorded_at' =>
            $this->recorded_at
                ?->toISOString(),

            'status' =>
            $this->status->value,

            'status_label' =>
            $this->status->label(),

            'difference_minutes' =>
            $this->difference_minutes,

            'source' =>
            $this->source->value,

            'source_label' =>
            $this->source->label(),

            'recorded_by_user_id' =>
            $this->recorded_by_user_id,

            'observation' =>
            $this->observation,

            'schedule_event' =>
            new AttendanceScheduleEventResource(
                $this->whenLoaded(
                    'scheduleEvent'
                )
            ),

            'created_at' =>
            $this->created_at
                ?->toISOString(),
        ];
    }
}
