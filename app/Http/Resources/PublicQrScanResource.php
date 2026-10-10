<?php

namespace App\Http\Resources;

use App\Enums\QrScanResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicQrScanResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $student =
            $this->qrCard?->student;

        $person =
            $student?->person;

        $mark =
            $this->attendanceMark;

        $event =
            $mark?->scheduleEvent;

        return [
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
            | Solo información necesaria para feedback
            |--------------------------------------------------------------------------
            */

            'student' =>
                $student && $person
                    ? [
                        'display_name' =>
                            collect([
                                $person->first_names,
                                $person->paternal_surname,
                                $person->maternal_surname,
                            ])
                                ->filter()
                                ->join(' '),
                    ]
                    : null,

            'attendance' =>
                $mark
                    ? [
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
                'No existe una matrícula activa para esta fecha.',

            'ambiguous_enrollment' =>
                'Existe una inconsistencia en la matrícula.',

            'no_schedule' =>
                'No existe un horario vigente.',

            'no_events_today' =>
                'No existen marcaciones programadas para hoy.',

            'outside_window' =>
                'La marcación se encuentra fuera del horario permitido.',

            'duplicate_mark' =>
                'Esta marcación ya fue registrada.',

            'non_working_day' =>
                'Hoy no se encuentra programado como día lectivo.',

            default =>
                'No fue posible registrar la asistencia.',
        };
    }
}