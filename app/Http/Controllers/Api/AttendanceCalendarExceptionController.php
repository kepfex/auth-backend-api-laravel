<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCalendarException\IndexAttendanceCalendarExceptionRequest;
use App\Http\Requests\AttendanceCalendarException\StoreAttendanceCalendarExceptionRequest;
use App\Http\Requests\AttendanceCalendarException\StoreScheduleOverrideExceptionRequest;
use App\Http\Requests\AttendanceCalendarException\UpdateAttendanceCalendarExceptionRequest;
use App\Http\Resources\AttendanceCalendarExceptionResource;
use App\Models\AttendanceCalendarException;
use App\Services\Attendance\AttendanceCalendarOverrideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceCalendarExceptionController extends Controller
{
    private const RELATIONS = [
        'academicYear',
        'educationalLevel',
        'gradeSection.grade.educationalLevel',
        'gradeSection.section',
        'overrideSchedule.events',
    ];

    public function index(
        IndexAttendanceCalendarExceptionRequest $request
    ): AnonymousResourceCollection {
        $filters =
            $request->validated();

        $query =
            AttendanceCalendarException::query()
            ->with(
                self::RELATIONS
            );

        if (
            isset(
                $filters['academic_year_id']
            )
        ) {
            $query->where(
                'academic_year_id',
                $filters['academic_year_id']
            );
        }

        if (
            isset(
                $filters['educational_level_id']
            )
        ) {
            $query->where(
                'educational_level_id',
                $filters['educational_level_id']
            );
        }

        if (
            isset(
                $filters['grade_section_id']
            )
        ) {
            $query->where(
                'grade_section_id',
                $filters['grade_section_id']
            );
        }

        if (
            isset(
                $filters['date']
            )
        ) {
            $query->whereDate(
                'date',
                $filters['date']
            );
        }

        if (
            isset(
                $filters['type']
            )
        ) {
            $query->where(
                'type',
                $filters['type']
            );
        }

        if (
            array_key_exists(
                'is_active',
                $filters
            )
        ) {
            $query->where(
                'is_active',
                $filters['is_active']
            );
        }

        $exceptions =
            $query
            ->orderByDesc('date')
            ->paginate(
                $filters['per_page']
                    ?? 15
            )
            ->withQueryString();

        return AttendanceCalendarExceptionResource::collection(
            $exceptions
        );
    }

    public function store(
        StoreAttendanceCalendarExceptionRequest $request
    ): AttendanceCalendarExceptionResource {
        $exception =
            AttendanceCalendarException::create(
                $request->validated()
            );

        $exception->load(
            self::RELATIONS
        );

        return new AttendanceCalendarExceptionResource(
            $exception
        );
    }

    public function show(
        AttendanceCalendarException $attendanceCalendarException
    ): AttendanceCalendarExceptionResource {
        $attendanceCalendarException
            ->load(
                self::RELATIONS
            );

        return new AttendanceCalendarExceptionResource(
            $attendanceCalendarException
        );
    }

    // Actualizar una excepción de calendario de asistencia existente.
    public function update(
        UpdateAttendanceCalendarExceptionRequest $request,
        AttendanceCalendarException $attendanceCalendarException
    ): AttendanceCalendarExceptionResource {
        $attendanceCalendarException
            ->update(
                $request->validated()
            );

        $attendanceCalendarException
            ->load(
                self::RELATIONS
            );

        return new AttendanceCalendarExceptionResource(
            $attendanceCalendarException
        );
    }

    // Almacenar una excepción de calendario de asistencia que anula el horario de asistencia para un día específico.
    public function storeScheduleOverride(
        StoreScheduleOverrideExceptionRequest $request,
        AttendanceCalendarOverrideService $service
    ): JsonResponse {
        $exception =
            $service->create(
                $request->validated()
            );

        return (
            new AttendanceCalendarExceptionResource(
                $exception
            )
        )
            ->response()
            ->setStatusCode(201);
    }
}
