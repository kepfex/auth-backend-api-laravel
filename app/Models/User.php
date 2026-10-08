<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Obtener el identificador que se almacenará en la reclamación de sujeto del JWT.
     *
     * @return mixed
     */    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // Relaciones

    /* User - Marcas de asistencia registradas */
    public function recordedAttendanceMarks(): HasMany
    {
        return $this->hasMany(
            AttendanceMark::class,
            'recorded_by_user_id'
        );
    }

    // Relaciones con justificaciones de asistencia
    public function submittedAttendanceJustifications(): HasMany
    {
        return $this->hasMany(
            AttendanceJustification::class,
            'submitted_by_user_id'
        );
    }

    // Relaciones con justificaciones de asistencia revisadas
    public function reviewedAttendanceJustifications(): HasMany
    {
        return $this->hasMany(
            AttendanceJustification::class,
            'reviewed_by_user_id'
        );
    }

    /* Como tenemos auditoría de emisión/revocación de tarjetas QR, podemos obtener las tarjetas emitidas y revocadas por este usuario */
    // Relaciones con QrCards emitidas
    public function issuedQrCards(): HasMany
    {
        return $this->hasMany(
            QrCard::class,
            'issued_by_user_id'
        );
    }
    // Relaciones con QrCards revocadas
    public function revokedQrCards(): HasMany
    {
        return $this->hasMany(
            QrCard::class,
            'revoked_by_user_id'
        );
    }
}
