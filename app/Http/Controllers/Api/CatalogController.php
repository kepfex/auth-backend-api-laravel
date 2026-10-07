<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceScheduleEventType;
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
}
