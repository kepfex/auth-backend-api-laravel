<?php

namespace App\Services\Attendance;

use App\Data\Attendance\AttendanceRegistrationResult;
use App\Enums\AttendanceMarkSource;
use App\Models\AttendanceDay;
use App\Models\AttendanceScheduleEvent;
use App\Models\Enrollment;
use App\Models\QrCard;
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
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Registro manual
    |--------------------------------------------------------------------------
    */

    public function registerManual(
        Enrollment $enrollment,
        AttendanceScheduleEvent $scheduleEvent,
        CarbonInterface $recordedAt,
        ?User $recordedBy = null,
        ?string $observation = null
    ): AttendanceDay {
        $result =
            $this->registerMatchedMark(
                enrollment: $enrollment,

                scheduleEvent: $scheduleEvent,

                recordedAt: $recordedAt,

                source: AttendanceMarkSource::Manual,

                recordedBy: $recordedBy,

                observation: $observation,
            );

        return $result->attendanceDay;
    }

    /*
    |--------------------------------------------------------------------------
    | Registro mediante QR
    |--------------------------------------------------------------------------
    */

    public function registerQr(
        Enrollment $enrollment,
        AttendanceScheduleEvent $scheduleEvent,
        QrCard $qrCard,
        CarbonInterface $recordedAt
    ): AttendanceRegistrationResult {
        if (
            $qrCard->student_id !==
            $enrollment->student_id
        ) {
            throw ValidationException::withMessages([
                'qr' => [
                    'La credencial QR no pertenece al estudiante de la matrícula.',
                ],
            ]);
        }

        return $this->registerMatchedMark(
            enrollment: $enrollment,

            scheduleEvent: $scheduleEvent,

            recordedAt: $recordedAt,

            source: AttendanceMarkSource::Qr,

            qrCard: $qrCard,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Registro común
    |--------------------------------------------------------------------------
    */

    private function registerMatchedMark(
        Enrollment $enrollment,
        AttendanceScheduleEvent $scheduleEvent,
        CarbonInterface $recordedAt,
        AttendanceMarkSource $source,
        ?User $recordedBy = null,
        ?string $observation = null,
        ?QrCard $qrCard = null
    ): AttendanceRegistrationResult {
        return DB::transaction(
            function () use (
                $enrollment,
                $scheduleEvent,
                $recordedAt,
                $source,
                $recordedBy,
                $observation,
                $qrCard
            ) {
                /*
                |--------------------------------------------------------------------------
                | Serializar registros de esta matrícula
                |--------------------------------------------------------------------------
                */

                $lockedEnrollment =
                    Enrollment::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $enrollment->id
                    );

                /*
                |--------------------------------------------------------------------------
                | Resolver horario
                |--------------------------------------------------------------------------
                */

                $schedule =
                    $this
                    ->scheduleResolver
                    ->resolve(
                        $lockedEnrollment,
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
                | Evento pertenece al horario
                |--------------------------------------------------------------------------
                */

                if (
                    $scheduleEvent
                    ->attendance_schedule_id !==
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
                | Día correcto
                |--------------------------------------------------------------------------
                */

                if (
                    $scheduleEvent
                    ->day_of_week
                    ->value !==
                    $recordedAt
                    ->dayOfWeekIso
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
                    $this
                    ->markStatusCalculator
                    ->calculate(
                        $scheduleEvent,
                        $recordedAt
                    );

                /*
                |--------------------------------------------------------------------------
                | Jornada
                |--------------------------------------------------------------------------
                */

                $attendanceDay =
                    AttendanceDay::query()
                    ->firstOrCreate(
                        [
                            'enrollment_id' =>
                            $lockedEnrollment->id,

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
                | Horario histórico inmutable
                |--------------------------------------------------------------------------
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
                | Anti duplicado
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
                | Crear Mark
                |--------------------------------------------------------------------------
                */

                $attendanceMark =
                    $attendanceDay
                    ->marks()
                    ->create([
                        'attendance_schedule_event_id' =>
                        $scheduleEvent->id,

                        'qr_card_id' =>
                        $qrCard?->id,

                        'event_type' =>
                        $scheduleEvent
                            ->event_type
                            ->value,

                        'recorded_at' =>
                        $recordedAt,

                        'status' =>
                        $calculation['status']->value,

                        'difference_minutes' =>
                        $calculation['difference_minutes'],

                        'source' =>
                        $source->value,

                        'recorded_by_user_id' =>
                        $recordedBy?->id,

                        'observation' =>
                        $observation,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Recalcular día
                |--------------------------------------------------------------------------
                */

                $this
                    ->dayStatusService
                    ->recalculate(
                        $attendanceDay
                    );

                $attendanceDay =
                    $attendanceDay
                    ->fresh()
                    ->load([
                        'enrollment.student.person',
                        'enrollment.academicYear',
                        'enrollment.gradeSection.grade.educationalLevel',
                        'enrollment.gradeSection.section',
                        'schedule.events',
                        'marks.scheduleEvent',
                    ]);

                $attendanceMark =
                    $attendanceMark
                    ->fresh()
                    ->load([
                        'scheduleEvent',
                        'qrCard',
                        'attendanceDay',
                    ]);

                return new AttendanceRegistrationResult(
                    attendanceDay: $attendanceDay,

                    attendanceMark: $attendanceMark,
                );
            }
        );
    }
}
