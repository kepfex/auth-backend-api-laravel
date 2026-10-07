<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceScheduleEventResource extends JsonResource
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

            'day_of_week' =>
            $this->day_of_week->value,

            'day_label' =>
            $this->day_of_week->label(),

            'sequence' =>
            $this->sequence,

            'event_type' =>
            $this->event_type->value,

            'event_type_label' =>
            $this->event_type->label(),

            /*
             * MySQL normalmente devuelve:
             * 08:00:00
             *
             * Para API preferimos:
             * 08:00
             */
            'expected_time' =>
            substr(
                (string) $this->expected_time,
                0,
                5
            ),

            'tolerance_minutes' =>
            $this->tolerance_minutes,

            'created_at' =>
            $this->created_at?->toISOString(),

            'updated_at' =>
            $this->updated_at?->toISOString(),
        ];
    }
}
