<?php

namespace App\Models;

use App\Enums\AttendanceDayStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceDay extends Model
{
    protected $fillable = [
        'enrollment_id',
        'attendance_schedule_id',
        'date',
        'status',
        'observation',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',

            'status' =>
            AttendanceDayStatus::class,
        ];
    }

    public function enrollment(): BelongsTo {
        return $this->belongsTo(
            Enrollment::class
        );
    }

    public function schedule(): BelongsTo {
        return $this->belongsTo(
            AttendanceSchedule::class,
            'attendance_schedule_id'
        );
    }

    public function marks(): HasMany {
        return $this->hasMany(
            AttendanceMark::class
        )
            ->orderBy('recorded_at');
    }

    // Reacion de uno a muchos con AttendanceJustification
    public function justifications(): HasMany
    {
        return $this->hasMany(
            AttendanceJustification::class
        );
    }
}
