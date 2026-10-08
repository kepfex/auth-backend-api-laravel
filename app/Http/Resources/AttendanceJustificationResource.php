<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceJustificationResource extends JsonResource
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

            'attendance_mark_id' =>
            $this->attendance_mark_id,

            'scope' =>
            $this->attendance_mark_id
                ? 'mark'
                : 'day',

            'reason' =>
            $this->reason,

            'status' =>
            $this->status->value,

            'status_label' =>
            $this->status->label(),

            /*
            |--------------------------------------------------------------------------
            | Archivo
            |--------------------------------------------------------------------------
            */

            'attachment' => [
                'exists' =>
                $this->attachment_path !== null,

                'name' =>
                $this->attachment_original_name,

                'mime_type' =>
                $this->attachment_mime_type,

                'size' =>
                $this->attachment_size,
            ],

            /*
            |--------------------------------------------------------------------------
            | Día resumido
            |--------------------------------------------------------------------------
            */

            'attendance_day' =>
            $this->whenLoaded(
                'attendanceDay',
                fn() => [
                    'id' =>
                    $this->attendanceDay->id,

                    'date' =>
                    $this->attendanceDay
                        ->date
                        ->format('Y-m-d'),

                    'status' =>
                    $this->attendanceDay
                        ->status
                        ->value,

                    'status_label' =>
                    $this->attendanceDay
                        ->status
                        ->label(),

                    'enrollment_id' =>
                    $this->attendanceDay
                        ->enrollment_id,
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Marcación específica
            |--------------------------------------------------------------------------
            */

            'attendance_mark' =>
            $this->when(
                $this->attendance_mark_id !== null &&
                    $this->relationLoaded(
                        'attendanceMark'
                    ),
                function () {
                    return [
                        'id' =>
                        $this->attendanceMark->id,

                        'event_type' =>
                        $this->attendanceMark
                            ->event_type
                            ->value,

                        'event_type_label' =>
                        $this->attendanceMark
                            ->event_type
                            ->label(),

                        'recorded_at' =>
                        $this->attendanceMark
                            ->recorded_at
                            ?->toISOString(),

                        'status' =>
                        $this->attendanceMark
                            ->status
                            ->value,

                        'status_label' =>
                        $this->attendanceMark
                            ->status
                            ->label(),

                        'difference_minutes' =>
                        $this->attendanceMark
                            ->difference_minutes,
                    ];
                }
            ),

            /*
            |--------------------------------------------------------------------------
            | Usuarios
            |--------------------------------------------------------------------------
            */

            'submitted_by' =>
            $this->whenLoaded(
                'submittedBy',
                fn() => [
                    'id' =>
                    $this->submittedBy->id,

                    'username' =>
                    $this->submittedBy->username,

                    'email' =>
                    $this->submittedBy->email,
                ]
            ),

            'reviewed_by' =>
            $this->when(
                $this->reviewed_by_user_id !== null &&
                    $this->relationLoaded(
                        'reviewedBy'
                    ),
                fn() => [
                    'id' =>
                    $this->reviewedBy->id,

                    'username' =>
                    $this->reviewedBy->username,

                    'email' =>
                    $this->reviewedBy->email,
                ]
            ),

            'review_comment' =>
            $this->review_comment,

            'reviewed_at' =>
            $this->reviewed_at
                ?->toISOString(),

            'created_at' =>
            $this->created_at
                ?->toISOString(),

            'updated_at' =>
            $this->updated_at
                ?->toISOString(),
        ];
    }
}
