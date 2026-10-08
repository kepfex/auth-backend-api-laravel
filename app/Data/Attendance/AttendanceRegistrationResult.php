<?php

namespace App\Data\Attendance;

use App\Models\AttendanceDay;
use App\Models\AttendanceMark;

final readonly class AttendanceRegistrationResult
{
    public function __construct(
        public AttendanceDay $attendanceDay,
        public AttendanceMark $attendanceMark,
    ) {
    }
}