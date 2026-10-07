<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSchedule extends Model
{
    protected $fillable = [
        'academic_year_id',
        'educational_level_id',
        'grade_section_id',
        'name',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(
            AcademicYear::class
        );
    }

    public function educationalLevel(): BelongsTo
    {
        return $this->belongsTo(
            EducationalLevel::class
        );
    }

    public function gradeSection(): BelongsTo
    {
        return $this->belongsTo(
            GradeSection::class
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            AttendanceScheduleEvent::class
        )
            ->orderBy('day_of_week')
            ->orderBy('sequence');
    }

    public function attendanceDays(): HasMany
    {
        return $this->hasMany(
            AttendanceDay::class
        );
    }
}
