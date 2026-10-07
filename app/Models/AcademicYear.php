<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;
    protected $table = 'academic_years';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the current academic year.
     *
     * @return AcademicYear|null
     */
    public static function currentAcademicYear(): ?self
    {   
        // Busca el año activo; si no hay ninguno, toma el más reciente por nombre
        return static::query()
            ->where('is_active', true)
            ->first()
            ?? static::query()
                ->orderByDesc('name')
                ->first();
    }

    // Año academico puede tener muchas matriculas, pero solo una por estudiante
    public function enrollments(): HasMany {
        return $this->hasMany(Enrollment::class);
    }

    // Año academico puede tener muchos horarios de asistencia
    public function attendanceSchedules(): HasMany {
        return $this->hasMany(AttendanceSchedule::class);
    }

    
}
