<?php

namespace App\Services\Qr;

use App\Enums\QrScanResult;
use App\Models\AttendanceDay;
use App\Models\Enrollment;
use App\Models\QrCard;
use App\Models\QrScan;
use App\Models\Student;
use App\Services\Attendance\AttendanceRegistrationService;
use App\Services\Attendance\AttendanceScheduleEventResolver;
use App\Services\Attendance\ScheduleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QrAttendanceScanService
{
    public function __construct(
        private readonly ScheduleResolver $scheduleResolver,
        private readonly AttendanceScheduleEventResolver $eventResolver,
        private readonly AttendanceRegistrationService $registrationService,
        private readonly QrScanLogger $scanLogger,
    ) {}

    public function process(
        string $rawToken
    ): QrScan {
        $rawToken =
            trim($rawToken);

        $scannedAt =
            CarbonImmutable::now(
                config('app.timezone')
            );

        return DB::transaction(
            function () use (
                $rawToken,
                $scannedAt
            ) {
                /*
                |--------------------------------------------------------------------------
                | Buscar QrCard
                |--------------------------------------------------------------------------
                */

                $qrCard =
                    QrCard::query()
                    ->where(
                        'uuid',
                        $rawToken
                    )
                    ->lockForUpdate()
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | QR desconocido
                |--------------------------------------------------------------------------
                */

                if (!$qrCard) {
                    return $this
                        ->scanLogger
                        ->log(
                            rawToken: $rawToken,

                            result: QrScanResult::Invalid,

                            reason: 'unknown_card',

                            scannedAt: $scannedAt,
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Revocada
                |--------------------------------------------------------------------------
                */

                if (
                    $qrCard
                    ->revoked_at !== null
                ) {
                    return $this
                        ->scanLogger
                        ->log(
                            rawToken: $rawToken,

                            result: QrScanResult::Rejected,

                            qrCard: $qrCard,

                            reason: 'revoked_card',

                            scannedAt: $scannedAt,
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Expirada
                |--------------------------------------------------------------------------
                */

                if (
                    $qrCard
                    ->expires_at !== null
                    &&
                    $qrCard
                    ->expires_at
                    ->lte($scannedAt)
                ) {
                    return $this
                        ->scanLogger
                        ->log(
                            rawToken: $rawToken,

                            result: QrScanResult::Rejected,

                            qrCard: $qrCard,

                            reason: 'expired_card',

                            scannedAt: $scannedAt,
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Bloquear estudiante
                |--------------------------------------------------------------------------
                */

                $student =
                    Student::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $qrCard->student_id
                    );

                /*
                |--------------------------------------------------------------------------
                | Matrícula vigente en la fecha actual
                |--------------------------------------------------------------------------
                */

                $enrollments =
                    Enrollment::query()
                    ->where(
                        'student_id',
                        $student->id
                    )
                    ->where(
                        'status',
                        'matriculado'
                    )
                    ->whereHas(
                        'academicYear',
                        function ($query) use ($scannedAt) {
                            $query
                                ->whereDate(
                                    'start_date',
                                    '<=',
                                    $scannedAt
                                        ->toDateString()
                                )
                                ->whereDate(
                                    'end_date',
                                    '>=',
                                    $scannedAt
                                        ->toDateString()
                                );
                        }
                    )
                    ->with([
                        'academicYear',
                        'gradeSection.grade.educationalLevel',
                        'gradeSection.section',
                    ])
                    ->lockForUpdate()
                    ->get();

                /*
                |--------------------------------------------------------------------------
                | Sin matrícula
                |--------------------------------------------------------------------------
                */

                if ($enrollments->isEmpty()) {
                    return $this
                        ->scanLogger
                        ->log(
                            rawToken: $rawToken,

                            result: QrScanResult::Rejected,

                            qrCard: $qrCard,

                            reason: 'no_active_enrollment',

                            scannedAt: $scannedAt,
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Inconsistencia: más de una
                |--------------------------------------------------------------------------
                */

                if (
                    $enrollments->count() > 1
                ) {
                    return $this
                        ->scanLogger
                        ->log(
                            rawToken: $rawToken,

                            result: QrScanResult::Rejected,

                            qrCard: $qrCard,

                            reason: 'ambiguous_enrollment',

                            scannedAt: $scannedAt,
                        );
                }

                /** @var Enrollment $enrollment */
                $enrollment =
                    $enrollments->first();

                /*
                |--------------------------------------------------------------------------
                | Resolver horario
                |--------------------------------------------------------------------------
                */

                $schedule =
                    $this
                    ->scheduleResolver
                    ->resolve(
                        $enrollment,
                        $scannedAt
                    );

                if (!$schedule) {
                    return $this
                        ->scanLogger
                        ->log(
                            rawToken: $rawToken,

                            result: QrScanResult::Rejected,

                            qrCard: $qrCard,

                            reason: 'no_schedule',

                            scannedAt: $scannedAt,
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Jornada existente
                |--------------------------------------------------------------------------
                */

                $attendanceDay =
                    AttendanceDay::query()
                    ->where(
                        'enrollment_id',
                        $enrollment->id
                    )
                    ->whereDate(
                        'date',
                        $scannedAt
                            ->toDateString()
                    )
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Resolver evento esperado
                |--------------------------------------------------------------------------
                */

                $resolution =
                    $this
                    ->eventResolver
                    ->resolve(
                        schedule: $schedule,

                        recordedAt: $scannedAt,

                        attendanceDay: $attendanceDay,
                    );

                /*
                |--------------------------------------------------------------------------
                | Sin evento
                |--------------------------------------------------------------------------
                */

                if (!$resolution['event']) {
                    $reason =
                        $resolution['reason'];

                    $result =
                        $reason ===
                        'duplicate_mark'
                        ? QrScanResult::Duplicate
                        : QrScanResult::Rejected;

                    return $this
                        ->scanLogger
                        ->log(
                            rawToken: $rawToken,

                            result: $result,

                            qrCard: $qrCard,

                            reason: $reason,

                            scannedAt: $scannedAt,
                        );
                }

                $scheduleEvent =
                    $resolution['event'];

                /*
                |--------------------------------------------------------------------------
                | Registrar AttendanceMark
                |--------------------------------------------------------------------------
                */

                try {
                    $registration =
                        $this
                        ->registrationService
                        ->registerQr(
                            enrollment: $enrollment,

                            scheduleEvent: $scheduleEvent,

                            qrCard: $qrCard,

                            recordedAt: $scannedAt,
                        );
                } catch (
                    ValidationException $exception
                ) {
                    /*
                     * Una carrera concurrente podría
                     * haber registrado el mismo evento.
                     */

                    if (
                        isset(
                            $exception
                                ->errors()['attendance_schedule_event_id']
                        )
                    ) {
                        return $this
                            ->scanLogger
                            ->log(
                                rawToken: $rawToken,

                                result: QrScanResult::Duplicate,

                                qrCard: $qrCard,

                                reason: 'duplicate_mark',

                                scannedAt: $scannedAt,
                            );
                    }

                    throw $exception;
                }

                /*
                |--------------------------------------------------------------------------
                | Scan aceptado
                |--------------------------------------------------------------------------
                */

                return $this
                    ->scanLogger
                    ->log(
                        rawToken: $rawToken,

                        result: QrScanResult::Accepted,

                        qrCard: $qrCard,

                        attendanceMark: $registration
                            ->attendanceMark,

                        scannedAt: $scannedAt,
                    );
            }
        )
            ->load([
                'qrCard.student.person',

                'attendanceMark.attendanceDay',

                'attendanceMark.scheduleEvent',

                'attendanceMark.qrCard',
            ]);
    }
}
