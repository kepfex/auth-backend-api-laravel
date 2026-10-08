<?php

namespace App\Data\Attendance;

use App\Models\AttendanceCalendarException;
use App\Models\AttendanceSchedule;

final readonly class ScheduleResolution
{
    public function __construct(
        public ?AttendanceSchedule $schedule,
        public ?string $reason = null,
        public ?AttendanceCalendarException $exception = null,
    ) {}

    public function hasSchedule(): bool
    {
        return $this->schedule !== null;
    }
}
