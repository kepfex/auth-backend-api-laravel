<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceJustificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceJustification\IndexAttendanceJustificationRequest;
use App\Http\Requests\AttendanceJustification\ReviewAttendanceJustificationRequest;
use App\Http\Requests\AttendanceJustification\StoreAttendanceJustificationRequest;
use App\Http\Resources\AttendanceJustificationResource;
use App\Models\AttendanceJustification;
use App\Services\Attendance\AttendanceJustificationService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceJustificationController extends Controller
{
    private const RELATIONS = [
        'attendanceDay.enrollment.student.person',
        'attendanceDay.enrollment.academicYear',
        'attendanceDay.enrollment.gradeSection.grade.educationalLevel',
        'attendanceDay.enrollment.gradeSection.section',

        'attendanceMark.scheduleEvent',

        'submittedBy',
        'reviewedBy',
    ];

    /**
     * Listado.
     */
    public function index(
        IndexAttendanceJustificationRequest $request
    ): AnonymousResourceCollection {
        $filters =
            $request->validated();

        $query =
            AttendanceJustification::query()
            ->with(
                self::RELATIONS
            );

        /*
        |--------------------------------------------------------------------------
        | Búsqueda
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filters['search'])
        ) {
            $search =
                trim(
                    $filters['search']
                );

            $query->whereHas(
                'attendanceDay.enrollment.student',
                function ($studentQuery) use ($search) {
                    $studentQuery
                        ->where(
                            'student_code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'person',
                            function ($personQuery) use ($search) {
                                $personQuery
                                    ->where(
                                        'document_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'first_names',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'paternal_surname',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'maternal_surname',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Jornada / marcación
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['attendance_day_id']
            )
        ) {
            $query->where(
                'attendance_day_id',
                $filters['attendance_day_id']
            );
        }

        if (
            isset(
                $filters['attendance_mark_id']
            )
        ) {
            $query->where(
                'attendance_mark_id',
                $filters['attendance_mark_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Estudiante
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['student_id']
            )
        ) {
            $query->whereHas(
                'attendanceDay.enrollment',
                fn($enrollmentQuery) =>
                $enrollmentQuery->where(
                    'student_id',
                    $filters['student_id']
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Año académico
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['academic_year_id']
            )
        ) {
            $query->whereHas(
                'attendanceDay.enrollment',
                fn($enrollmentQuery) =>
                $enrollmentQuery->where(
                    'academic_year_id',
                    $filters['academic_year_id']
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nivel
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['educational_level_id']
            )
        ) {
            $query->whereHas(
                'attendanceDay.enrollment.gradeSection.grade',
                fn($gradeQuery) =>
                $gradeQuery->where(
                    'educational_level_id',
                    $filters['educational_level_id']
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Aula
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['grade_section_id']
            )
        ) {
            $query->whereHas(
                'attendanceDay.enrollment',
                fn($enrollmentQuery) =>
                $enrollmentQuery->where(
                    'grade_section_id',
                    $filters['grade_section_id']
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['status']
            )
        ) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Scope
        |--------------------------------------------------------------------------
        */

        if (
            ($filters['scope'] ?? null) ===
            'day'
        ) {
            $query->whereNull(
                'attendance_mark_id'
            );
        }

        if (
            ($filters['scope'] ?? null) ===
            'mark'
        ) {
            $query->whereNotNull(
                'attendance_mark_id'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fechas de asistencia
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['date_from']
            )
        ) {
            $query->whereHas(
                'attendanceDay',
                fn($dayQuery) =>
                $dayQuery->whereDate(
                    'date',
                    '>=',
                    $filters['date_from']
                )
            );
        }

        if (
            isset(
                $filters['date_to']
            )
        ) {
            $query->whereHas(
                'attendanceDay',
                fn($dayQuery) =>
                $dayQuery->whereDate(
                    'date',
                    '<=',
                    $filters['date_to']
                )
            );
        }

        $justifications =
            $query
            ->orderByRaw(
                "
                    CASE status
                        WHEN 'pending' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'rejected' THEN 3
                        ELSE 4
                    END
                    "
            )
            ->orderByDesc(
                'created_at'
            )
            ->paginate(
                $filters['per_page']
                    ?? 15
            )
            ->withQueryString();

        return AttendanceJustificationResource::collection(
            $justifications
        );
    }

    /**
     * Crear.
     */
    public function store(
        StoreAttendanceJustificationRequest $request,
        AttendanceJustificationService $service
    ): JsonResponse {
        $data =
            $request->validated();

        $justification =
            $service->submit(
                attendanceDayId: $data['attendance_day_id'],

                attendanceMarkId: $data['attendance_mark_id']
                    ?? null,

                reason: $data['reason'],

                submittedBy: $request->user('api'),

                attachment: $request->file(
                    'attachment'
                ),
            );

        return (
            new AttendanceJustificationResource(
                $justification
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Detalle.
     */
    public function show(
        AttendanceJustification $attendanceJustification
    ): AttendanceJustificationResource {
        $attendanceJustification
            ->load(
                self::RELATIONS
            );

        return new AttendanceJustificationResource(
            $attendanceJustification
        );
    }

    /**
     * Aprobar o rechazar.
     */
    public function review(
        ReviewAttendanceJustificationRequest $request,
        AttendanceJustification $attendanceJustification,
        AttendanceJustificationService $service
    ): AttendanceJustificationResource {
        $data =
            $request->validated();

        $status =
            AttendanceJustificationStatus::from(
                $data['status']
            );

        $justification =
            $service->review(
                justification: $attendanceJustification,

                status: $status,

                reviewedBy: $request->user('api'),

                reviewComment: $data['review_comment']
                    ?? null,
            );

        return new AttendanceJustificationResource(
            $justification
        );
    }

    /**
     * Descargar archivo privado.
     */
    public function attachment(
        AttendanceJustification $attendanceJustification
    ): StreamedResponse {
        if (
            !$attendanceJustification
                ->attachment_path
        ) {
            abort(
                404,
                'La justificación no tiene archivo adjunto.'
            );
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        if (
            !$disk->exists(
                $attendanceJustification
                    ->attachment_path
            )
        ) {
            abort(
                404,
                'El archivo adjunto no se encuentra disponible.'
            );
        }

        return $disk->download(
            $attendanceJustification
                ->attachment_path,

            $attendanceJustification
                ->attachment_original_name
                ?? 'justificacion'
        );
    }
}
