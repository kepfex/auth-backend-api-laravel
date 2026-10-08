<?php

namespace App\Services\Qr;

use App\Models\QrCard;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrCardService
{
    public function issue(
        Student $student,
        ?User $issuedBy = null,
        ?CarbonInterface $expiresAt = null
    ): QrCard {
        return DB::transaction(
            function () use (
                $student,
                $issuedBy,
                $expiresAt
            ) {
                /*
                |--------------------------------------------------------------------------
                | Bloqueo del Student
                |--------------------------------------------------------------------------
                |
                | Serializa emisiones concurrentes
                | para el mismo estudiante.
                |
                */

                $lockedStudent =
                    Student::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $student->id
                    );

                /*
                |--------------------------------------------------------------------------
                | Solo una tarjeta utilizable
                |--------------------------------------------------------------------------
                */

                $hasActiveCard =
                    QrCard::query()
                    ->where(
                        'student_id',
                        $lockedStudent->id
                    )
                    ->usable()
                    ->exists();

                if ($hasActiveCard) {
                    throw ValidationException::withMessages([
                        'student_id' => [
                            'El estudiante ya tiene una tarjeta QR activa.',
                        ],
                    ]);
                }

                return QrCard::create([
                    'student_id' =>
                    $lockedStudent->id,

                    'uuid' =>
                    Str::uuid()
                        ->toString(),

                    'issued_at' =>
                    now(),

                    'issued_by_user_id' =>
                    $issuedBy?->id,

                    'expires_at' =>
                    $expiresAt,
                ]);
            }
        );
    }

    public function revoke(
        QrCard $qrCard,
        ?User $revokedBy,
        string $reason
    ): QrCard {
        return DB::transaction(
            function () use (
                $qrCard,
                $revokedBy,
                $reason
            ) {
                $qrCard =
                    QrCard::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $qrCard->id
                    );

                if ($qrCard->revoked_at) {
                    throw ValidationException::withMessages([
                        'qr_card' => [
                            'La tarjeta QR ya se encuentra revocada.',
                        ],
                    ]);
                }

                if (
                    $qrCard->expires_at &&
                    $qrCard->expires_at->isPast()
                ) {
                    throw ValidationException::withMessages([
                        'qr_card' => [
                            'La tarjeta QR ya se encuentra expirada.',
                        ],
                    ]);
                }

                $qrCard->update([
                    'revoked_at' =>
                    now(),

                    'revoked_by_user_id' =>
                    $revokedBy?->id,

                    'revocation_reason' =>
                    trim($reason),
                ]);

                return $qrCard->fresh();
            }
        );
    }

    public function reissue(
        Student $student,
        ?User $issuedBy,
        string $reason,
        ?CarbonInterface $expiresAt = null
    ): QrCard {
        return DB::transaction(
            function () use (
                $student,
                $issuedBy,
                $reason,
                $expiresAt
            ) {
                $lockedStudent =
                    Student::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $student->id
                    );

                /*
                |--------------------------------------------------------------------------
                | Revocar tarjeta actualmente utilizable
                |--------------------------------------------------------------------------
                */

                $currentCard =
                    QrCard::query()
                    ->where(
                        'student_id',
                        $lockedStudent->id
                    )
                    ->usable()
                    ->lockForUpdate()
                    ->first();

                if ($currentCard) {
                    $currentCard->update([
                        'revoked_at' =>
                        now(),

                        'revoked_by_user_id' =>
                        $issuedBy?->id,

                        'revocation_reason' =>
                        trim($reason),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Crear nueva
                |--------------------------------------------------------------------------
                */

                return QrCard::create([
                    'student_id' =>
                    $lockedStudent->id,

                    'uuid' =>
                    Str::uuid()
                        ->toString(),

                    'issued_at' =>
                    now(),

                    'issued_by_user_id' =>
                    $issuedBy?->id,

                    'expires_at' =>
                    $expiresAt,
                ]);
            }
        );
    }

    public function current(
        Student $student
    ): ?QrCard {
        return QrCard::query()
            ->where(
                'student_id',
                $student->id
            )
            ->usable()
            ->latest('issued_at')
            ->first();
    }
}
