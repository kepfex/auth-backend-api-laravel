<?php

namespace App\Services\Attendance;

use App\Models\AttendanceSchedule;
use App\Models\Enrollment;
use Carbon\CarbonInterface;

// Este servicio responde:
// Para esta matrícula y esta fecha, ¿qué horario corresponde?

/*La prioridad será:
1. Horario específico del aula
2. Horario general del nivel
3. Si no existe ninguno → null */

// Este servicio será reutilizado posteriormente por el QR.
class ScheduleResolver
{
    public function resolve(
        Enrollment $enrollment,
        CarbonInterface $date
    ): ?AttendanceSchedule {
        $enrollment->loadMissing([
            'gradeSection.grade',
        ]);

        $gradeSection =
            $enrollment->gradeSection;

        if (!$gradeSection) {
            return null;
        }

        $educationalLevelId =
            $gradeSection
            ->grade
            ->educational_level_id;

        $baseQuery =
            AttendanceSchedule::query()
            ->where(
                'academic_year_id',
                $enrollment->academic_year_id
            )
            ->where(
                'educational_level_id',
                $educationalLevelId
            )
            ->where(
                'is_active',
                true
            )
            ->whereDate(
                'valid_from',
                '<=',
                $date->toDateString()
            )
            ->whereDate(
                'valid_until',
                '>=',
                $date->toDateString()
            );

        /*
        |--------------------------------------------------------------------------
        | 1. Horario específico del aula
        |--------------------------------------------------------------------------
        */

        $specificSchedule =
            (clone $baseQuery)
            ->where(
                'grade_section_id',
                $enrollment->grade_section_id
            )
            ->orderByDesc(
                'valid_from'
            )
            ->first();

        if ($specificSchedule) {
            return $specificSchedule;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Horario general del nivel
        |--------------------------------------------------------------------------
        */

        return (clone $baseQuery)
            ->whereNull(
                'grade_section_id'
            )
            ->orderByDesc(
                'valid_from'
            )
            ->first();
    }
}
