<?php

namespace App\Models;

use App\Enums\AttendanceScheduleEventType;
use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceScheduleEvent extends Model
{
    protected $fillable = [
        'attendance_schedule_id',
        'day_of_week',
        'sequence',
        'event_type',
        'expected_time',
        'tolerance_minutes',
        'window_before_minutes',
        'window_after_minutes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' =>
            Weekday::class,

            'event_type' =>
            AttendanceScheduleEventType::class,

            'sequence' =>
            'integer',

            'tolerance_minutes' =>
            'integer',

            'window_before_minutes' =>
            'integer',

            'window_after_minutes' =>
            'integer',
        ];
    }

    // Este evento pertenece a un horario de asistencia
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSchedule::class,
            'attendance_schedule_id'
        );
    }

    // Este evento tiene muchas marcas de asistencia
    public function attendanceMarks(): HasMany
    {
        return $this->hasMany(
            AttendanceMark::class,
            'attendance_schedule_event_id'
        );
    }
}
