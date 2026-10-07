<?php

namespace App\Models;

use App\Enums\AttendanceJustificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceJustification extends Model
{
    protected $fillable = [
        'attendance_day_id',
        'attendance_mark_id',
        'submitted_by_user_id',
        'reason',
        'attachment_path',
        'status',
        'reviewed_by_user_id',
        'review_comment',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' =>
            AttendanceJustificationStatus::class,

            'reviewed_at' =>
            'datetime',
        ];
    }

    public function attendanceDay(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceDay::class
        );
    }

    public function attendanceMark(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceMark::class
        );
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'submitted_by_user_id'
        );
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_user_id'
        );
    }
}
