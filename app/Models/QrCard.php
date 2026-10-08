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
        'issued_at',
        'issued_by_user_id',
        'expires_at',
        'revoked_at',
        'revoked_by_user_id',
        'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    // Este QrCard pertenece a un estudiante
    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }

    // Este QrCard fue emitido por un usuario
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'issued_by_user_id'
        );
    }

    // Este QrCard fue revocado por un usuario
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revoked_by_user_id'
        );
    }

    // Este QrCard tiene muchos escaneos
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

    /*
    |--------------------------------------------------------------------------
    | Estado calculado
    |--------------------------------------------------------------------------
    */

    // Devuelve el estado de ciclo de vida del QrCard
    public function lifecycleStatus(): QrCardStatus
    {
        if ($this->revoked_at !== null) {
            return QrCardStatus::Revoked;
        }

        if (
            $this->expires_at !== null &&
            $this->expires_at->isPast()
        ) {
            return QrCardStatus::Expired;
        }

        return QrCardStatus::Active;
    }

    /*
    |--------------------------------------------------------------------------
    | Tarjetas utilizables
    |--------------------------------------------------------------------------
    */

    // Devuelve un scope para obtener solo los QrCards utilizables
    public function scopeUsable(
        Builder $query,
        ?CarbonInterface $at = null
    ): Builder {
        $at ??= now();

        return $query
            ->whereNull('revoked_at')
            ->where(
                function (Builder $query) use ($at) {
                    $query
                        ->whereNull(
                            'expires_at'
                        )
                        ->orWhere(
                            'expires_at',
                            '>',
                            $at
                        );
                }
            );
    }

    // Devuelve si el QrCard es utilizable
    public function isUsable(): bool
    {
        return $this->lifecycleStatus() ===
            QrCardStatus::Active;
    }
}
