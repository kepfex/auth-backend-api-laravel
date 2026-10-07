<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreManualAttendanceMarkRequest;
use App\Http\Resources\AttendanceDayResource;
use App\Models\AttendanceScheduleEvent;
use App\Models\Enrollment;
use App\Services\Attendance\AttendanceRegistrationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class AttendanceMarkController extends Controller
{
    public function storeManual(
        StoreManualAttendanceMarkRequest $request,
        AttendanceRegistrationService $registrationService
    ): JsonResponse {
        $data =
            $request->validated();

        $enrollment =
            Enrollment::query()
            ->findOrFail(
                $data['enrollment_id']
            );

        $scheduleEvent =
            AttendanceScheduleEvent::query()
            ->findOrFail(
                $data['attendance_schedule_event_id']
            );

        /*
        |--------------------------------------------------------------------------
        | El servidor interpreta la fecha/hora
        | según la zona configurada por la aplicación.
        |--------------------------------------------------------------------------
        */

        $recordedAt =
            CarbonImmutable::parse(
                $data['recorded_at'],
                config('app.timezone')
            );

        $attendanceDay =
            $registrationService
            ->registerManual(
                enrollment: $enrollment,

                scheduleEvent: $scheduleEvent,

                recordedAt: $recordedAt,

                recordedBy: $request->user(
                    'api'
                ),

                observation: $data['observation']
                    ?? null,
            );

        return (
            new AttendanceDayResource(
                $attendanceDay
            )
        )
            ->response()
            ->setStatusCode(201);
    }
}
