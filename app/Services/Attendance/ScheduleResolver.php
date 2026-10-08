<?php

namespace App\Services\Attendance;

use App\Data\Attendance\ScheduleResolution;
use App\Enums\AttendanceCalendarExceptionType;
use App\Enums\AttendanceScheduleType;
use App\Models\AttendanceSchedule;
use App\Models\Enrollment;
use Carbon\CarbonInterface;

// Este servicio responde:
// Para esta matrícula y esta fecha, ¿qué horario corresponde?

/*La prioridad será:
1. Horario específico del aula
2. Horario general del nivel
3. Si no existe ninguno → null 
4. Si hay una excepción del calendario, se prioriza sobre los horarios regulares.
*/

// Este servicio sera utilizado por el servicio de asistencia para determinar el horario que corresponde a un estudiante en una fecha determinada. 
class ScheduleResolver
{
    public function __construct(
        private readonly AttendanceCalendarExceptionResolver $exceptionResolver,
    ) {
    }

    /**
     * Compatibilidad con servicios existentes.
     */
    public function resolve(
        Enrollment $enrollment,
        CarbonInterface $date
    ): ?AttendanceSchedule {
        return $this
            ->resolveDetailed(
                $enrollment,
                $date
            )
            ->schedule;
    }

    public function resolveDetailed(
        Enrollment $enrollment,
        CarbonInterface $date
    ): ScheduleResolution {
        $enrollment->loadMissing([
            'gradeSection.grade',
        ]);

        $gradeSection =
            $enrollment->gradeSection;

        if (!$gradeSection) {
            return new ScheduleResolution(
                schedule: null,
                reason: 'no_grade_section',
            );
        }

        $levelId =
            $gradeSection
                ->grade
                ->educational_level_id;

        /*
        |--------------------------------------------------------------------------
        | 1. Excepción del calendario
        |--------------------------------------------------------------------------
        */

        $exception =
            $this
                ->exceptionResolver
                ->resolve(
                    $enrollment,
                    $date
                );

        if ($exception) {
            /*
             * Día no lectivo
             */
            if (
                $exception->type ===
                AttendanceCalendarExceptionType::NonWorking
            ) {
                return new ScheduleResolution(
                    schedule: null,
                    reason: 'non_working_day',
                    exception: $exception,
                );
            }

            /*
             * Horario excepcional
             */
            if (
                $exception->type ===
                AttendanceCalendarExceptionType::ScheduleOverride
            ) {
                $schedule =
                    $exception
                        ->overrideSchedule;

                if (
                    !$schedule ||
                    !$schedule->is_active
                ) {
                    return new ScheduleResolution(
                        schedule: null,
                        reason: 'inactive_override_schedule',
                        exception: $exception,
                    );
                }

                return new ScheduleResolution(
                    schedule: $schedule,
                    exception: $exception,
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Horario regular
        |--------------------------------------------------------------------------
        */

        $baseQuery =
            AttendanceSchedule::query()
                ->where(
                    'academic_year_id',
                    $enrollment->academic_year_id
                )
                ->where(
                    'educational_level_id',
                    $levelId
                )
                ->where(
                    'schedule_type',
                    AttendanceScheduleType::Regular->value
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
        | Aula específica
        |--------------------------------------------------------------------------
        */

        $specific =
            (clone $baseQuery)
                ->where(
                    'grade_section_id',
                    $enrollment
                        ->grade_section_id
                )
                ->orderByDesc(
                    'valid_from'
                )
                ->first();

        if ($specific) {
            return new ScheduleResolution(
                schedule: $specific
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nivel
        |--------------------------------------------------------------------------
        */

        $general =
            (clone $baseQuery)
                ->whereNull(
                    'grade_section_id'
                )
                ->orderByDesc(
                    'valid_from'
                )
                ->first();

        if ($general) {
            return new ScheduleResolution(
                schedule: $general
            );
        }

        return new ScheduleResolution(
            schedule: null,
            reason: 'no_schedule',
        );
    }
}