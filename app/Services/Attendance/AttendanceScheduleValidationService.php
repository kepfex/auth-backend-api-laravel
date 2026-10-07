<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceScheduleEventType;
use App\Models\AcademicYear;
use App\Models\AttendanceSchedule;
use App\Models\GradeSection;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class AttendanceScheduleValidationService
{
    public function validate(
        Validator $validator,
        array $data,
        ?AttendanceSchedule $currentSchedule = null
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Si las reglas básicas ya fallaron, no hacemos consultas adicionales
        |--------------------------------------------------------------------------
        */

        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener valores efectivos
        |--------------------------------------------------------------------------
        |
        | En UPDATE algunos campos no vienen en el request.
        | En ese caso utilizamos los valores actuales del Schedule.
        |
        */

        $academicYearId =
            $data['academic_year_id']
            ?? $currentSchedule?->academic_year_id;

        $educationalLevelId =
            $data['educational_level_id']
            ?? $currentSchedule?->educational_level_id;

        $gradeSectionId =
            array_key_exists('grade_section_id', $data)
            ? $data['grade_section_id']
            : $currentSchedule?->grade_section_id;

        $validFrom =
            $data['valid_from']
            ?? $currentSchedule?->valid_from?->format('Y-m-d');

        $validUntil =
            $data['valid_until']
            ?? $currentSchedule?->valid_until?->format('Y-m-d');

        $isActive =
            array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : (bool) $currentSchedule?->is_active;

        /*
        |--------------------------------------------------------------------------
        | Año académico
        |--------------------------------------------------------------------------
        */

        $academicYear = AcademicYear::query()
            ->find($academicYearId);

        if (!$academicYear) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar vigencia dentro del año académico
        |--------------------------------------------------------------------------
        */

        $from = Carbon::parse($validFrom);
        $until = Carbon::parse($validUntil);

        if (
            $from->lt($academicYear->start_date) ||
            $from->gt($academicYear->end_date)
        ) {
            $validator->errors()->add(
                'valid_from',
                'La fecha de inicio debe estar dentro del año académico seleccionado.'
            );
        }

        if (
            $until->lt($academicYear->start_date) ||
            $until->gt($academicYear->end_date)
        ) {
            $validator->errors()->add(
                'valid_until',
                'La fecha final debe estar dentro del año académico seleccionado.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validar aula específica
        |--------------------------------------------------------------------------
        */

        if ($gradeSectionId !== null) {
            $gradeSection = GradeSection::query()
                ->with('grade')
                ->find($gradeSectionId);

            if ($gradeSection) {
                if (
                    $gradeSection->academic_year_id !==
                    $academicYearId
                ) {
                    $validator->errors()->add(
                        'grade_section_id',
                        'El aula seleccionada no pertenece al año académico indicado.'
                    );
                }

                if (
                    $gradeSection->grade->educational_level_id !==
                    $educationalLevelId
                ) {
                    $validator->errors()->add(
                        'grade_section_id',
                        'El aula seleccionada no pertenece al nivel educativo indicado.'
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validar solapamiento
        |--------------------------------------------------------------------------
        |
        | Solo importa entre schedules ACTIVOS del mismo scope.
        |
        | General:
        | grade_section_id = NULL
        |
        | Específico:
        | grade_section_id = X
        |
        | Un específico SÍ puede solaparse con el general porque tiene
        | prioridad sobre él.
        |
        */

        if ($isActive) {
            $overlapQuery = AttendanceSchedule::query()
                ->where(
                    'academic_year_id',
                    $academicYearId
                )
                ->where(
                    'educational_level_id',
                    $educationalLevelId
                )
                ->where('is_active', true)
                ->whereDate(
                    'valid_from',
                    '<=',
                    $validUntil
                )
                ->whereDate(
                    'valid_until',
                    '>=',
                    $validFrom
                );

            if ($gradeSectionId === null) {
                $overlapQuery->whereNull(
                    'grade_section_id'
                );
            } else {
                $overlapQuery->where(
                    'grade_section_id',
                    $gradeSectionId
                );
            }

            if ($currentSchedule) {
                $overlapQuery->whereKeyNot(
                    $currentSchedule->id
                );
            }

            if ($overlapQuery->exists()) {
                $validator->errors()->add(
                    'valid_from',
                    'Ya existe un horario activo con una vigencia superpuesta para el mismo ámbito.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validar eventos
        |--------------------------------------------------------------------------
        */

        if (array_key_exists('events', $data)) {
            $this->validateEvents(
                $validator,
                $data['events']
            );
        }
    }

    private function validateEvents(
        Validator $validator,
        array $events
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Agrupar eventos por día
        |--------------------------------------------------------------------------
        */

        $eventsByDay = collect($events)
            ->groupBy('day_of_week');

        foreach ($eventsByDay as $day => $dayEvents) {
            /*
            |--------------------------------------------------------------------------
            | Ordenar según sequence
            |--------------------------------------------------------------------------
            */

            $ordered = $dayEvents
                ->sortBy('sequence')
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Las secuencias deben ser consecutivas: 1,2,3...
            |--------------------------------------------------------------------------
            */

            foreach (
                $ordered as $index => $event
            ) {
                $expectedSequence =
                    $index + 1;

                if (
                    (int) $event['sequence'] !==
                    $expectedSequence
                ) {
                    $validator->errors()->add(
                        'events',
                        "Las secuencias del día {$day} deben comenzar en 1 y ser consecutivas."
                    );

                    break;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Debe existir un número par de eventos
            |--------------------------------------------------------------------------
            |
            | entry + exit
            | entry + exit + entry + exit
            |
            */

            if ($ordered->count() % 2 !== 0) {
                $validator->errors()->add(
                    'events',
                    "El día {$day} debe contener pares completos de entrada y salida."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validar alternancia
            |--------------------------------------------------------------------------
            */

            foreach (
                $ordered as $index => $event
            ) {
                $expectedType =
                    $index % 2 === 0
                    ? AttendanceScheduleEventType::Entry->value
                    : AttendanceScheduleEventType::Exit->value;

                if (
                    $event['event_type'] !==
                    $expectedType
                ) {
                    $validator->errors()->add(
                        'events',
                        "Los eventos del día {$day} deben alternarse: entrada, salida, entrada, salida."
                    );

                    break;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Validar que las horas sean estrictamente ascendentes
            |--------------------------------------------------------------------------
            */

            $previousTime = null;

            foreach ($ordered as $event) {
                $currentTime =
                    Carbon::createFromFormat(
                        'H:i',
                        $event['expected_time']
                    );

                if (
                    $previousTime &&
                    $currentTime->lte(
                        $previousTime
                    )
                ) {
                    $validator->errors()->add(
                        'events',
                        "Las horas del día {$day} deben respetar el orden cronológico."
                    );

                    break;
                }

                $previousTime =
                    $currentTime;
            }
        }
    }
}
