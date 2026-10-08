<?php

namespace App\Models;

use App\Enums\AttendanceCalendarExceptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCalendarException extends Model
{
    protected $fillable = [
        'academic_year_id',
        'educational_level_id',
        'grade_section_id',
        'attendance_schedule_id',
        'date',
        'type',
        'name',
        'reason',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',

            'type' =>
            AttendanceCalendarExceptionType::class,

            'is_active' =>
            'boolean',
        ];
    }

    // Está relación es opcional, ya que no todas las excepciones de calendario de asistencia están asociadas a un horario de asistencia.
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(
            AcademicYear::class
        );
    }

    // Está relación es opcional, ya que no todas las excepciones de calendario de asistencia están asociadas a un nivel educativo.
    public function educationalLevel(): BelongsTo
    {
        return $this->belongsTo(
            EducationalLevel::class
        );
    }

    // Está relación es opcional, ya que no todas las excepciones de calendario de asistencia están asociadas a un grado o sección.
    public function gradeSection(): BelongsTo
    {
        return $this->belongsTo(
            GradeSection::class
        );
    }

    // Está relación es opcional, ya que no todas las excepciones de calendario de asistencia están asociadas a un horario de asistencia.
    public function overrideSchedule(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSchedule::class,
            'attendance_schedule_id'
        );
    }
}
