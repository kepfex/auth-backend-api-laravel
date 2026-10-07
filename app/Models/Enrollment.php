<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'grade_section_id',
        'enrollment_date',
        'status',
        'observations',
    ];

    protected function casts(): array {
        return [
            'enrollment_date' => 'date',
            'status' => EnrollmentStatus::class,
        ];
    }

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class);
    }

    // Academic Year - Año académico
    public function academicYear(): BelongsTo {
        return $this->belongsTo(AcademicYear::class);
    }

    // Classrroom - Salon de clases
    public function gradeSection(): BelongsTo {
        return $this->belongsTo(GradeSection::class);
    }

    // Attendance Days - Días de asistencia
    public function attendanceDays(): HasMany {
        return $this->hasMany(AttendanceDay::class);
    }
}
