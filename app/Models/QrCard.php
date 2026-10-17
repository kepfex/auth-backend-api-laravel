<?php

namespace App\Models;

use App\Enums\QrCardStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCard extends Model
{
    protected $fillable = [
        'student_id',
        'uuid',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Este QrCard pertenece a un estudiante
    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }

    // Este QrCard tiene muchos escaneos - Escaneos realizados con este QR.
    public function scans(): HasMany
    {
        return $this->hasMany(
            QrScan::class
        );
    }

    // Este QrCard tiene muchas marcas de asistencia
    public function attendanceMarks(): HasMany
    {
        return $this->hasMany(
            AttendanceMark::class
        );
    }

    /**
     * Filtrar únicamente códigos QR habilitados.
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }


    /**
     * Determinar si el QR está habilitado.
     */
    public function isUsable(): bool
    {
        return $this->is_active;
    }
}
