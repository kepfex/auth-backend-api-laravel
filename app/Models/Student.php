<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['person_id', 'student_code', 'status'];

    // Relación con la tabla persons
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    // Relación con la tabla intermedia student_guardians
    public function studentGuardians(): HasMany {
        return $this->hasMany(StudentGuardian::class);
    }

    // Relación con apoderados a través de la tabla intermedia student_guardians
    public function guardians(): BelongsToMany {
        return $this->belongsToMany(
            Guardian::class,
            'student_guardians'
        )
            ->withPivot([
                'relationship',
                'is_primary',
                'receives_notifications',
            ])
            ->withTimestamps();
    }

    // estudiante puede tener muchas matriculas, pero solo una por año académico
    public function enrollments(): HasMany {
        return $this->hasMany(Enrollment::class);
    }

    // estudiante puede tener muchas tarjetas QR
    public function qrCards(): HasMany {
        return $this->hasMany(QrCard::class);
    }
}
