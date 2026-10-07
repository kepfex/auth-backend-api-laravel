<?php

namespace App\Models;

use App\Enums\AttendanceScheduleEventType;
use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScheduleEvent extends Model
{
    protected $fillable = [
        'attendance_schedule_id',
        'day_of_week',
        'sequence',
        'event_type',
        'expected_time',
        'tolerance_minutes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => Weekday::class,
            'event_type' =>
            AttendanceScheduleEventType::class,
            'sequence' => 'integer',
            'tolerance_minutes' => 'integer',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSchedule::class,
            'attendance_schedule_id'
        );
    }
}
