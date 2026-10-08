<?php

namespace App\Http\Resources;

use App\Enums\QrScanResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrScanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student =
            $this->qrCard?->student;

        $person =
            $student?->person;

        $mark =
            $this->attendanceMark;

        $event =
            $mark?->scheduleEvent;

        return [
            'id' =>
            $this->id,

            'accepted' =>
            $this->result ===
                QrScanResult::Accepted,

            'result' =>
            $this->result->value,

            'result_label' =>
            $this->result->label(),

            'reason' =>
            $this->reason,

            'message' =>
            $this->message(),

            'scanned_at' =>
            $this->scanned_at
                ?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Estudiante
            |--------------------------------------------------------------------------
            */

            'student' =>
            $student && $person
                ? [
                    'id' =>
                    $student->id,

                    'student_code' =>
                    $student->student_code,

                    'first_names' =>
                    $person->first_names,

                    'paternal_surname' =>
                    $person->paternal_surname,

                    'maternal_surname' =>
                    $person->maternal_surname,
                ]
                : null,

            /*
            |--------------------------------------------------------------------------
            | Marcación creada
            |--------------------------------------------------------------------------
            */

            'attendance' =>
            $mark
                ? [
                    'attendance_day_id' =>
                    $mark
                        ->attendance_day_id,

                    'attendance_mark_id' =>
                    $mark->id,

                    'date' =>
                    $mark
                        ->attendanceDay
                        ?->date
                        ?->format('Y-m-d'),

                    'day_status' =>
                    $mark
                        ->attendanceDay
                        ?->status
                        ?->value,

                    'event_type' =>
                    $mark
                        ->event_type
                        ->value,

                    'event_type_label' =>
                    $mark
                        ->event_type
                        ->label(),

                    'expected_time' =>
                    $event
                        ? substr(
                            (string)
                            $event
                                ->expected_time,
                            0,
                            5
                        )
                        : null,

                    'recorded_at' =>
                    $mark
                        ->recorded_at
                        ?->toISOString(),

                    'status' =>
                    $mark
                        ->status
                        ->value,

                    'status_label' =>
                    $mark
                        ->status
                        ->label(),

                    'difference_minutes' =>
                    $mark
                        ->difference_minutes,
                ]
                : null,
        ];
    }

    private function message(): string
    {
        if (
            $this->result ===
            QrScanResult::Accepted
        ) {
            $type =
                $this
                ->attendanceMark
                ?->event_type
                ?->label()
                ?? 'Asistencia';

            return "{$type} registrada correctamente.";
        }

        return match ($this->reason) {
            'unknown_card' =>
            'El código QR no está registrado.',

            'revoked_card' =>
            'La tarjeta QR se encuentra revocada.',

            'expired_card' =>
            'La tarjeta QR se encuentra expirada.',

            'no_active_enrollment' =>
            'El estudiante no tiene una matrícula activa para esta fecha.',

            'ambiguous_enrollment' =>
            'Se encontró una inconsistencia en las matrículas del estudiante.',

            'no_schedule' =>
            'No existe un horario vigente para el estudiante.',

            'no_events_today' =>
            'No existen marcaciones programadas para hoy.',

            'outside_window' =>
            'La marcación se encuentra fuera del horario permitido.',

            'duplicate_mark' =>
            'Esta marcación ya fue registrada.',

            default =>
            'No se pudo registrar la asistencia.',

            'non_working_day' =>
            'Hoy no se encuentra programado como día lectivo.',

            'inactive_override_schedule' =>
            'El horario excepcional configurado para hoy no se encuentra disponible.',
        };
    }
}
