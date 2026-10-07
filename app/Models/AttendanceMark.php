<?php

namespace App\Models;

use App\Enums\AttendanceMarkSource;
use App\Enums\AttendanceMarkStatus;
use App\Enums\AttendanceScheduleEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceMark extends Model
{
    protected $fillable = [
        'attendance_day_id',
        'attendance_schedule_event_id',
        'event_type',
        'recorded_at',
        'status',
        'difference_minutes',
        'source',
        'recorded_by_user_id',
        'observation',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',

            'event_type' =>
            AttendanceScheduleEventType::class,

            'status' =>
            AttendanceMarkStatus::class,

            'source' =>
            AttendanceMarkSource::class,

            'difference_minutes' =>
            'integer',
        ];
    }

    public function attendanceDay(): BelongsTo {
        return $this->belongsTo(
            AttendanceDay::class
        );
    }

    public function scheduleEvent(): BelongsTo {
        return $this->belongsTo(
            AttendanceScheduleEvent::class,
            'attendance_schedule_event_id'
        );
    }

    public function recordedBy(): BelongsTo {
        return $this->belongsTo(
            User::class,
            'recorded_by_user_id'
        );
    }

    // Relacion de uno a muchos con AttendanceJustification
    public function justifications(): HasMany
    {
        return $this->hasMany(
            AttendanceJustification::class
        );
    }
}
