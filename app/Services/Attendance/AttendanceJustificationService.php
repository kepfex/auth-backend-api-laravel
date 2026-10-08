<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceDayStatus;
use App\Enums\AttendanceJustificationStatus;
use App\Enums\AttendanceMarkStatus;
use App\Models\AttendanceDay;
use App\Models\AttendanceJustification;
use App\Models\AttendanceMark;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AttendanceJustificationService
{
    public function submit(
        int $attendanceDayId,
        ?int $attendanceMarkId,
        string $reason,
        User $submittedBy,
        ?UploadedFile $attachment = null
    ): AttendanceJustification {
        $storedPath = null;

        try {
            return DB::transaction(
                function () use (
                    $attendanceDayId,
                    $attendanceMarkId,
                    $reason,
                    $submittedBy,
                    $attachment,
                    &$storedPath
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Bloquear jornada
                    |--------------------------------------------------------------------------
                    */

                    $attendanceDay =
                        AttendanceDay::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $attendanceDayId
                        );

                    $attendanceMark = null;

                    /*
                    |--------------------------------------------------------------------------
                    | Justificación específica de marcación
                    |--------------------------------------------------------------------------
                    */

                    if ($attendanceMarkId !== null) {
                        $attendanceMark =
                            AttendanceMark::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $attendanceMarkId
                            );

                        if (
                            $attendanceMark
                            ->attendance_day_id !==
                            $attendanceDay->id
                        ) {
                            throw ValidationException::withMessages([
                                'attendance_mark_id' => [
                                    'La marcación seleccionada no pertenece a la jornada indicada.',
                                ],
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Solo justificamos incidencias reales
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !in_array(
                                $attendanceMark->status,
                                [
                                    AttendanceMarkStatus::Late,
                                    AttendanceMarkStatus::Early,
                                ],
                                true
                            )
                        ) {
                            throw ValidationException::withMessages([
                                'attendance_mark_id' => [
                                    'Solo pueden justificarse marcaciones con tardanza o salida anticipada.',
                                ],
                            ]);
                        }
                    } else {
                        /*
                        |--------------------------------------------------------------------------
                        | Justificación general del día
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !in_array(
                                $attendanceDay->status,
                                [
                                    AttendanceDayStatus::Absent,
                                    AttendanceDayStatus::Partial,
                                ],
                                true
                            )
                        ) {
                            throw ValidationException::withMessages([
                                'attendance_day_id' => [
                                    'Solo pueden justificarse jornadas ausentes o con asistencia parcial.',
                                ],
                            ]);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Evitar pending duplicada o nueva sobre una ya aprobada
                    |--------------------------------------------------------------------------
                    */

                    $existingQuery =
                        AttendanceJustification::query()
                        ->where(
                            'attendance_day_id',
                            $attendanceDay->id
                        );

                    if ($attendanceMark) {
                        $existingQuery->where(
                            'attendance_mark_id',
                            $attendanceMark->id
                        );
                    } else {
                        $existingQuery->whereNull(
                            'attendance_mark_id'
                        );
                    }

                    $existing =
                        $existingQuery
                        ->whereIn(
                            'status',
                            [
                                AttendanceJustificationStatus::Pending->value,
                                AttendanceJustificationStatus::Approved->value,
                            ]
                        )
                        ->orderByDesc('id')
                        ->first();

                    if ($existing) {
                        if (
                            $existing->status ===
                            AttendanceJustificationStatus::Pending
                        ) {
                            throw ValidationException::withMessages([
                                'attendance_day_id' => [
                                    'Ya existe una justificación pendiente para este caso.',
                                ],
                            ]);
                        }

                        throw ValidationException::withMessages([
                            'attendance_day_id' => [
                                'Este caso ya cuenta con una justificación aprobada.',
                            ],
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Archivo privado
                    |--------------------------------------------------------------------------
                    */

                    $attachmentOriginalName = null;
                    $attachmentMimeType = null;
                    $attachmentSize = null;

                    if ($attachment) {
                        $directory =
                            'attendance-justifications/'
                            . $attendanceDay->date->format('Y/m');

                        $storedPath =
                            $attachment->store(
                                $directory,
                                'local'
                            );

                        $attachmentOriginalName =
                            $attachment->getClientOriginalName();

                        $attachmentMimeType =
                            $attachment->getMimeType();

                        $attachmentSize =
                            $attachment->getSize();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Crear justificación
                    |--------------------------------------------------------------------------
                    */

                    $justification =
                        AttendanceJustification::create([
                            'attendance_day_id' =>
                            $attendanceDay->id,

                            'attendance_mark_id' =>
                            $attendanceMark?->id,

                            'submitted_by_user_id' =>
                            $submittedBy->id,

                            'reason' =>
                            trim($reason),

                            'attachment_path' =>
                            $storedPath,

                            'attachment_original_name' =>
                            $attachmentOriginalName,

                            'attachment_mime_type' =>
                            $attachmentMimeType,

                            'attachment_size' =>
                            $attachmentSize,

                            'status' =>
                            AttendanceJustificationStatus::Pending->value,
                        ]);

                    return $justification
                        ->load([
                            'attendanceDay.enrollment.student.person',
                            'attendanceMark.scheduleEvent',
                            'submittedBy',
                            'reviewedBy',
                        ]);
                }
            );
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | La BD sí tiene transaction.
            | El sistema de archivos no.
            |--------------------------------------------------------------------------
            |
            | Si algo falla luego de almacenar el archivo,
            | evitamos dejar un archivo huérfano.
            |
            */

            if (
                $storedPath &&
                Storage::disk('local')
                ->exists($storedPath)
            ) {
                Storage::disk('local')
                    ->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function review(
        AttendanceJustification $justification,
        AttendanceJustificationStatus $status,
        User $reviewedBy,
        ?string $reviewComment = null
    ): AttendanceJustification {
        return DB::transaction(
            function () use (
                $justification,
                $status,
                $reviewedBy,
                $reviewComment
            ) {
                $justification =
                    AttendanceJustification::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $justification->id
                    );

                /*
                |--------------------------------------------------------------------------
                | Solo pending puede revisarse
                |--------------------------------------------------------------------------
                */

                if (
                    $justification->status !==
                    AttendanceJustificationStatus::Pending
                ) {
                    throw ValidationException::withMessages([
                        'status' => [
                            'Esta justificación ya fue revisada y no puede modificarse.',
                        ],
                    ]);
                }

                if (
                    !in_array(
                        $status,
                        [
                            AttendanceJustificationStatus::Approved,
                            AttendanceJustificationStatus::Rejected,
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'status' => [
                            'El estado de revisión no es válido.',
                        ],
                    ]);
                }

                $justification->update([
                    'status' =>
                    $status->value,

                    'reviewed_by_user_id' =>
                    $reviewedBy->id,

                    'review_comment' =>
                    $reviewComment
                        ? trim(
                            $reviewComment
                        )
                        : null,

                    'reviewed_at' =>
                    now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | IMPORTANTE
                |--------------------------------------------------------------------------
                |
                | NO modificamos:
                |
                | AttendanceDay.status
                | AttendanceMark.status
                |
                | El hecho histórico permanece intacto.
                |
                */

                return $justification
                    ->fresh()
                    ->load([
                        'attendanceDay.enrollment.student.person',
                        'attendanceMark.scheduleEvent',
                        'submittedBy',
                        'reviewedBy',
                    ]);
            }
        );
    }
}
