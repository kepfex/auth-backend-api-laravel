<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceDayStatus;
use App\Models\AttendanceDay;

// Ahora necesitamos convertir:
    /*
    1 de 4 marcaciones
    2 de 4
    4 de 4
    */

    // Obtendremos:
    /*
    0/4 → pending
    1/4 → partial
    2/4 → partial
    3/4 → partial
    4/4 → present
    */
// y cuando en el futuro finalicemos el día: 0/4 → absent

class AttendanceDayStatusService
{
    public function recalculate(
        AttendanceDay $attendanceDay,
        bool $finalize = false
    ): AttendanceDay {
        $attendanceDay->loadMissing([
            'schedule.events',
        ]);

        $weekday =
            $attendanceDay
                ->date
                ->dayOfWeekIso;

        /*
        |--------------------------------------------------------------------------
        | Eventos que realmente correspondían a ese día
        |--------------------------------------------------------------------------
        */

        $expectedEventIds =
            $attendanceDay
                ->schedule
                ->events
                ->filter(
                    fn ($event) =>
                        $event->day_of_week->value ===
                        $weekday
                )
                ->pluck('id');

        $expectedCount =
            $expectedEventIds->count();

        /*
        |--------------------------------------------------------------------------
        | Caso excepcional: el horario no tiene eventos ese día
        |--------------------------------------------------------------------------
        */

        if ($expectedCount === 0) {
            return $this->setStatus(
                $attendanceDay,
                AttendanceDayStatus::Pending
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Marcaciones válidamente asociadas a eventos esperados
        |--------------------------------------------------------------------------
        */

        $matchedCount =
            $attendanceDay
                ->marks()
                ->whereIn(
                    'attendance_schedule_event_id',
                    $expectedEventIds
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Todos los eventos completados
        |--------------------------------------------------------------------------
        */

        if (
            $matchedCount >=
            $expectedCount
        ) {
            return $this->setStatus(
                $attendanceDay,
                AttendanceDayStatus::Present
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existe al menos una marcación
        |--------------------------------------------------------------------------
        */

        if ($matchedCount > 0) {
            return $this->setStatus(
                $attendanceDay,
                AttendanceDayStatus::Partial
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Finalización del día
        |--------------------------------------------------------------------------
        |
        | Si terminó la jornada y no hubo ninguna marcación:
        |
        | absent
        |
        | Todavía NO automatizamos el cierre diario.
        |
        */

        if ($finalize) {
            return $this->setStatus(
                $attendanceDay,
                AttendanceDayStatus::Absent
            );
        }

        return $this->setStatus(
            $attendanceDay,
            AttendanceDayStatus::Pending
        );
    }

    public function finalize(
        AttendanceDay $attendanceDay
    ): AttendanceDay {
        return $this->recalculate(
            $attendanceDay,
            true
        );
    }

    private function setStatus(
        AttendanceDay $attendanceDay,
        AttendanceDayStatus $status
    ): AttendanceDay {
        if (
            $attendanceDay->status ===
            $status
        ) {
            return $attendanceDay;
        }

        $attendanceDay->status =
            $status;

        $attendanceDay->save();

        return $attendanceDay;
    }
}