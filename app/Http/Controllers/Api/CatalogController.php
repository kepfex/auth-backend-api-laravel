<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceCalendarExceptionType;
use App\Enums\AttendanceDayStatus;
use App\Enums\AttendanceJustificationStatus;
use App\Enums\AttendanceMarkSource;
use App\Enums\AttendanceMarkStatus;
use App\Enums\AttendanceScheduleEventType;
use App\Enums\AttendanceScheduleType;
use App\Enums\EnrollmentStatus;
use App\Enums\GuardianRelationship;
use App\Enums\Weekday;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function guardianRelationships(): JsonResponse
    {
        return response()->json([
            'data' => GuardianRelationship::options(),
        ]);
    }

    public function enrollmentStatuses()
    {
        return response()->json([
            'data' => EnrollmentStatus::options(),
        ]);
    }

    public function attendanceScheduleEventTypes(): JsonResponse
    {
        return response()->json([
            'data' =>
            AttendanceScheduleEventType::options(),
        ]);
    }

    public function weekdays(): JsonResponse
    {
        return response()->json([
            'data' =>
            Weekday::options(),
        ]);
    }

    public function attendanceDayStatuses(): JsonResponse
    {
        return response()->json([
            'data' =>
            AttendanceDayStatus::options(),
        ]);
    }

    public function attendanceMarkStatuses(): JsonResponse
    {
        return response()->json([
            'data' =>
            AttendanceMarkStatus::options(),
        ]);
    }

    // Método para obtener los tipos de fuentes de marcas de asistencia
    public function attendanceMarkSources(): JsonResponse
    {
        return response()->json([
            'data' =>
            AttendanceMarkSource::options(),
        ]);
    }

    // Método para obtener los tipos de justificación de asistencia
    public function attendanceJustificationStatuses(): JsonResponse
    {
        return response()->json([
            'data' =>
            AttendanceJustificationStatus::options(),
        ]);
    }

    // Método para obtener los tipos de excepciones del calendario de asistencia y los tipos de horarios de asistencia
    public function attendanceCalendarExceptionTypes(): JsonResponse
    {
        return response()->json([
            'data' =>
            AttendanceCalendarExceptionType::options(),
        ]);
    }

    // Método para obtener los tipos de horarios de asistencia
    public function attendanceScheduleTypes(): JsonResponse
    {
        return response()->json([
            'data' =>
            AttendanceScheduleType::options(),
        ]);
    }
}
