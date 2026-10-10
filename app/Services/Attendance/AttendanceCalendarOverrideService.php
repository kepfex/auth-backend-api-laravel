<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceCalendarExceptionType;
use App\Enums\AttendanceScheduleType;
use App\Models\AttendanceCalendarException;
use App\Models\AttendanceSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceCalendarOverrideService
{
    public function create(
        array $data
    ): AttendanceCalendarException {
        return DB::transaction(
            function () use ($data) {
                $date =
                    CarbonImmutable::parse(
                        $data['date']
                    );

                /*
                |--------------------------------------------------------------------------
                | Schedule excepcional
                |--------------------------------------------------------------------------
                */

                $schedule =
                    AttendanceSchedule::create([
                        'academic_year_id' =>
                            $data['academic_year_id'],

                        'educational_level_id' =>
                            $data['educational_level_id'],

                        'grade_section_id' =>
                            $data['grade_section_id']
                            ?? null,

                        'name' =>
                            $data['schedule']['name'],

                        'schedule_type' =>
                            AttendanceScheduleType::Override
                                ->value,

                        'valid_from' =>
                            $data['date'],

                        'valid_until' =>
                            $data['date'],

                        'is_active' =>
                            $data['is_active']
                            ?? true,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Eventos
                |--------------------------------------------------------------------------
                */

                $events =
                    collect(
                        $data['schedule']['events']
                    )
                        ->values()
                        ->map(
                            fn (
                                array $event,
                                int $index
                            ) => [
                                ...$event,

                                'day_of_week' =>
                                    $date
                                        ->dayOfWeekIso,

                                'sequence' =>
                                    $index + 1,
                            ]
                        )
                        ->all();

                $schedule
                    ->events()
                    ->createMany(
                        $events
                    );

                /*
                |--------------------------------------------------------------------------
                | Excepción
                |--------------------------------------------------------------------------
                */

                $exception =
                    AttendanceCalendarException::create([
                        'academic_year_id' =>
                            $data['academic_year_id'],

                        'educational_level_id' =>
                            $data['educational_level_id'],

                        'grade_section_id' =>
                            $data['grade_section_id']
                            ?? null,

                        'attendance_schedule_id' =>
                            $schedule->id,

                        'date' =>
                            $data['date'],

                        'type' =>
                            AttendanceCalendarExceptionType::ScheduleOverride
                                ->value,

                        'name' =>
                            $data['name'],

                        'reason' =>
                            $data['reason']
                            ?? null,

                        'is_active' =>
                            $data['is_active']
                            ?? true,
                    ]);

                return $exception
                    ->load([
                        'academicYear',
                        'educationalLevel',
                        'gradeSection.grade.educationalLevel',
                        'gradeSection.section',
                        'overrideSchedule.events',
                    ]);
            }
        );
    }
}