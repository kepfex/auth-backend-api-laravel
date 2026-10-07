<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceMarkStatus;
use App\Enums\AttendanceScheduleEventType;
use App\Models\AttendanceScheduleEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/* Aquí definimos formalmente:
difference_minutes
=
hora real - hora esperada

Y usamos la tolerancia del evento.*/

// Esto también deja claro que la tolerancia puede aplicarse distinto según el tipo:
 /* ENTRY
tolerancia = minutos permitidos después

EXIT
tolerancia = minutos permitidos antes */


class AttendanceMarkStatusCalculator
{
    /**
     * @return array{
     *     status: AttendanceMarkStatus,
     *     difference_minutes: int
     * }
     */
    public function calculate(
        AttendanceScheduleEvent $event,
        CarbonInterface $recordedAt
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Construir fecha/hora esperada
        |--------------------------------------------------------------------------
        */

        $expectedAt =
            CarbonImmutable::parse(
                $recordedAt->toDateString()
                . ' '
                . $event->expected_time,
                $recordedAt->getTimezone()
            );

        /*
        |--------------------------------------------------------------------------
        | real - esperado
        |--------------------------------------------------------------------------
        */

        $differenceSeconds =
            $recordedAt->getTimestamp()
            - $expectedAt->getTimestamp();

        $differenceMinutes =
            intdiv(
                $differenceSeconds,
                60
            );

        $tolerance =
            $event->tolerance_minutes;

        /*
        |--------------------------------------------------------------------------
        | Entrada
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        |
        | esperado   08:00
        | tolerancia 10
        |
        | 08:10 → on_time
        | 08:11 → late
        |
        */

        if (
            $event->event_type ===
            AttendanceScheduleEventType::Entry
        ) {
            $status =
                $differenceMinutes >
                $tolerance
                    ? AttendanceMarkStatus::Late
                    : AttendanceMarkStatus::OnTime;

            return [
                'status' => $status,
                'difference_minutes' =>
                    $differenceMinutes,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Salida
        |--------------------------------------------------------------------------
        |
        | En una salida interesa detectar
        | si salió antes de lo permitido.
        |
        | Ejemplo:
        |
        | esperado   16:15
        | tolerancia 5
        |
        | 16:10 → on_time
        | 16:09 → early
        | 16:20 → on_time
        |
        */

        $status =
            $differenceMinutes <
            -$tolerance
                ? AttendanceMarkStatus::Early
                : AttendanceMarkStatus::OnTime;

        return [
            'status' => $status,
            'difference_minutes' =>
                $differenceMinutes,
        ];
    }
}