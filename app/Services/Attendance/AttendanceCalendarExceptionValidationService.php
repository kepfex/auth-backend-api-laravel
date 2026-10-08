<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceCalendarExceptionType;
use App\Enums\AttendanceScheduleType;
use App\Models\AcademicYear;
use App\Models\AttendanceCalendarException;
use App\Models\AttendanceSchedule;
use App\Models\GradeSection;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class AttendanceCalendarExceptionValidationService
{
    public function validate(
        Validator $validator,
        array $data,
        ?AttendanceCalendarException $currentException = null
    ): void {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $academicYearId =
            $data['academic_year_id']
            ?? $currentException?->academic_year_id;

        $educationalLevelId =
            array_key_exists(
                'educational_level_id',
                $data
            )
            ? $data['educational_level_id']
            : $currentException?->educational_level_id;

        $gradeSectionId =
            array_key_exists(
                'grade_section_id',
                $data
            )
            ? $data['grade_section_id']
            : $currentException?->grade_section_id;

        $scheduleId =
            array_key_exists(
                'attendance_schedule_id',
                $data
            )
            ? $data['attendance_schedule_id']
            : $currentException?->attendance_schedule_id;

        $type =
            $data['type']
            ?? $currentException?->type?->value;

        $date =
            $data['date']
            ?? $currentException?->date?->format(
                'Y-m-d'
            );

        $isActive =
            array_key_exists(
                'is_active',
                $data
            )
            ? (bool) $data['is_active']
            : (bool) (
                $currentException
                ?->is_active
                ?? true
            );

        /*
        |--------------------------------------------------------------------------
        | Año académico
        |--------------------------------------------------------------------------
        */

        $academicYear =
            AcademicYear::query()
            ->find(
                $academicYearId
            );

        if (!$academicYear) {
            return;
        }

        $exceptionDate =
            Carbon::parse($date);

        if (
            $exceptionDate->lt(
                $academicYear->start_date
            ) ||
            $exceptionDate->gt(
                $academicYear->end_date
            )
        ) {
            $validator
                ->errors()
                ->add(
                    'date',
                    'La fecha debe encontrarse dentro del año académico seleccionado.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Aula
        |--------------------------------------------------------------------------
        */

        if ($gradeSectionId !== null) {
            if ($educationalLevelId === null) {
                $validator
                    ->errors()
                    ->add(
                        'educational_level_id',
                        'Una excepción por aula debe indicar su nivel educativo.'
                    );

                return;
            }

            $gradeSection =
                GradeSection::query()
                ->with('grade')
                ->find(
                    $gradeSectionId
                );

            if ($gradeSection) {
                if (
                    $gradeSection
                    ->academic_year_id !==
                    $academicYearId
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'grade_section_id',
                            'El aula no pertenece al año académico indicado.'
                        );
                }

                if (
                    $gradeSection
                    ->grade
                    ->educational_level_id !==
                    $educationalLevelId
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'grade_section_id',
                            'El aula no pertenece al nivel educativo indicado.'
                        );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Día no lectivo
        |--------------------------------------------------------------------------
        */

        if (
            $type ===
            AttendanceCalendarExceptionType::NonWorking->value
        ) {
            if ($scheduleId !== null) {
                $validator
                    ->errors()
                    ->add(
                        'attendance_schedule_id',
                        'Un día no lectivo no debe tener un horario asociado.'
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Horario excepcional
        |--------------------------------------------------------------------------
        */

        if (
            $type ===
            AttendanceCalendarExceptionType::ScheduleOverride->value
        ) {
            if (
                $educationalLevelId ===
                null
            ) {
                $validator
                    ->errors()
                    ->add(
                        'educational_level_id',
                        'Un horario excepcional debe pertenecer a un nivel educativo.'
                    );
            }

            if ($scheduleId === null) {
                $validator
                    ->errors()
                    ->add(
                        'attendance_schedule_id',
                        'Debe seleccionar el horario excepcional que se aplicará.'
                    );
            } else {
                $schedule =
                    AttendanceSchedule::query()
                    ->find(
                        $scheduleId
                    );

                if ($schedule) {
                    if (
                        $schedule
                        ->schedule_type !==
                        AttendanceScheduleType::Override
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'attendance_schedule_id',
                                'El horario seleccionado no está definido como horario excepcional.'
                            );
                    }

                    if (
                        $schedule
                        ->academic_year_id !==
                        $academicYearId
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'attendance_schedule_id',
                                'El horario excepcional no pertenece al año académico indicado.'
                            );
                    }

                    if (
                        $schedule
                        ->educational_level_id !==
                        $educationalLevelId
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'attendance_schedule_id',
                                'El horario excepcional no pertenece al nivel educativo indicado.'
                            );
                    }

                    if (
                        $schedule
                        ->grade_section_id !==
                        $gradeSectionId
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'attendance_schedule_id',
                                'El ámbito del horario excepcional no coincide con el ámbito de la excepción.'
                            );
                    }

                    /*
                     * Para simplificar y proteger
                     * el histórico:
                     *
                     * un override representa
                     * exactamente una fecha.
                     */
                    if (
                        !$schedule
                            ->valid_from
                            ->isSameDay(
                                $exceptionDate
                            )
                        ||
                        !$schedule
                            ->valid_until
                            ->isSameDay(
                                $exceptionDate
                            )
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'attendance_schedule_id',
                                'El horario excepcional debe tener la misma fecha inicial y final que la excepción.'
                            );
                    }

                    if (
                        !$schedule
                            ->is_active
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'attendance_schedule_id',
                                'El horario excepcional seleccionado se encuentra inactivo.'
                            );
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | No permitir dos excepciones activas
        | para exactamente el mismo scope y fecha
        |--------------------------------------------------------------------------
        */

        if (!$isActive) {
            return;
        }

        $duplicateQuery =
            AttendanceCalendarException::query()
            ->where(
                'academic_year_id',
                $academicYearId
            )
            ->whereDate(
                'date',
                $date
            )
            ->where(
                'is_active',
                true
            );

        if ($educationalLevelId === null) {
            $duplicateQuery
                ->whereNull(
                    'educational_level_id'
                );
        } else {
            $duplicateQuery
                ->where(
                    'educational_level_id',
                    $educationalLevelId
                );
        }

        if ($gradeSectionId === null) {
            $duplicateQuery
                ->whereNull(
                    'grade_section_id'
                );
        } else {
            $duplicateQuery
                ->where(
                    'grade_section_id',
                    $gradeSectionId
                );
        }

        if ($currentException) {
            $duplicateQuery
                ->whereKeyNot(
                    $currentException->id
                );
        }

        if (
            $duplicateQuery->exists()
        ) {
            $validator
                ->errors()
                ->add(
                    'date',
                    'Ya existe una excepción activa para la misma fecha y ámbito.'
                );
        }
    }
}
