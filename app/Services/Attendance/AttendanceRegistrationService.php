<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceMarkSource;
use App\Models\AttendanceDay;
use App\Models\AttendanceScheduleEvent;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Este servicio será extremadamente importante porque más adelante:
    // registro manual
    // QR
    // quizá biométrico
// deberán terminar utilizando la misma lógica.

class AttendanceRegistrationService
{
    public function __construct(
        private readonly ScheduleResolver $scheduleResolver,
        private readonly AttendanceMarkStatusCalculator $markStatusCalculator,
        private readonly AttendanceDayStatusService $dayStatusService,
    ) {
    }

    public function registerManual(
        Enrollment $enrollment,
        AttendanceScheduleEvent $scheduleEvent,
        CarbonInterface $recordedAt,
        ?User $recordedBy = null,
        ?string $observation = null
    ): AttendanceDay {
        return $this->registerMatchedMark(
            enrollment: $enrollment,
            scheduleEvent: $scheduleEvent,
            recordedAt: $recordedAt,
            source: AttendanceMarkSource::Manual,
            recordedBy: $recordedBy,
            observation: $observation,
        );
    }

    private function registerMatchedMark(
        Enrollment $enrollment,
        AttendanceScheduleEvent $scheduleEvent,
        CarbonInterface $recordedAt,
        AttendanceMarkSource $source,
        ?User $recordedBy = null,
        ?string $observation = null
    ): AttendanceDay {
        /*
        |--------------------------------------------------------------------------
        | Resolver horario correspondiente
        |--------------------------------------------------------------------------
        */

        $schedule =
            $this->scheduleResolver
                ->resolve(
                    $enrollment,
                    $recordedAt
                );

        if (!$schedule) {
            throw ValidationException::withMessages([
                'recorded_at' => [
                    'No existe un horario de asistencia vigente para el estudiante en la fecha indicada.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | El evento debe pertenecer al horario resuelto
        |--------------------------------------------------------------------------
        */

        if (
            $scheduleEvent->attendance_schedule_id !==
            $schedule->id
        ) {
            throw ValidationException::withMessages([
                'attendance_schedule_event_id' => [
                    'El evento seleccionado no pertenece al horario aplicable al estudiante.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | El evento debe corresponder al día de la semana
        |--------------------------------------------------------------------------
        */

        if (
            $scheduleEvent->day_of_week->value !==
            $recordedAt->dayOfWeekIso
        ) {
            throw ValidationException::withMessages([
                'attendance_schedule_event_id' => [
                    'El evento seleccionado no corresponde al día de la semana de la marcación.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Calcular puntualidad
        |--------------------------------------------------------------------------
        */

        $calculation =
            $this->markStatusCalculator
                ->calculate(
                    $scheduleEvent,
                    $recordedAt
                );

        return DB::transaction(
            function () use (
                $enrollment,
                $schedule,
                $scheduleEvent,
                $recordedAt,
                $source,
                $recordedBy,
                $observation,
                $calculation
            ) {
                /*
                |--------------------------------------------------------------------------
                | Obtener o crear jornada
                |--------------------------------------------------------------------------
                */

                $attendanceDay =
                    AttendanceDay::query()
                        ->firstOrCreate(
                            [
                                'enrollment_id' =>
                                    $enrollment->id,

                                'date' =>
                                    $recordedAt
                                        ->toDateString(),
                            ],
                            [
                                'attendance_schedule_id' =>
                                    $schedule->id,

                                'status' =>
                                    'pending',
                            ]
                        );

                /*
                |--------------------------------------------------------------------------
                | Protección histórica
                |--------------------------------------------------------------------------
                |
                | El día nunca debe cambiar de horario una vez creado.
                |
                */

                if (
                    $attendanceDay
                        ->attendance_schedule_id !==
                    $schedule->id
                ) {
                    throw ValidationException::withMessages([
                        'recorded_at' => [
                            'La jornada ya fue creada utilizando un horario diferente.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Evitar duplicado amigablemente
                |--------------------------------------------------------------------------
                */

                $alreadyRegistered =
                    $attendanceDay
                        ->marks()
                        ->where(
                            'attendance_schedule_event_id',
                            $scheduleEvent->id
                        )
                        ->exists();

                if ($alreadyRegistered) {
                    throw ValidationException::withMessages([
                        'attendance_schedule_event_id' => [
                            'Este evento de asistencia ya fue registrado para el estudiante.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Crear marcación
                |--------------------------------------------------------------------------
                */

                $attendanceDay
                    ->marks()
                    ->create([
                        'attendance_schedule_event_id' =>
                            $scheduleEvent->id,

                        'event_type' =>
                            $scheduleEvent
                                ->event_type
                                ->value,

                        'recorded_at' =>
                            $recordedAt,

                        'status' =>
                            $calculation['status']
                                ->value,

                        'difference_minutes' =>
                            $calculation[
                                'difference_minutes'
                            ],

                        'source' =>
                            $source->value,

                        'recorded_by_user_id' =>
                            $recordedBy?->id,

                        'observation' =>
                            $observation,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Recalcular jornada
                |--------------------------------------------------------------------------
                */

                $attendanceDay =
                    $this
                        ->dayStatusService
                        ->recalculate(
                            $attendanceDay
                        );

                /*
                |--------------------------------------------------------------------------
                | Respuesta completa
                |--------------------------------------------------------------------------
                */

                return $attendanceDay
                    ->fresh()
                    ->load([
                        'enrollment.student.person',
                        'enrollment.academicYear',
                        'enrollment.gradeSection.grade.educationalLevel',
                        'enrollment.gradeSection.section',

                        'schedule.events',

                        'marks.scheduleEvent',
                    ]);
            }
        );
    }
}