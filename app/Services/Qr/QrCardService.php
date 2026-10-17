<?php

namespace App\Services\Qr;

use App\Models\QrCard;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QrCardService
{
    /**
     * Crear el QR único de un estudiante.
     *
     * Si ya existe, devuelve el mismo registro
     * sin generar otro UUID.
     */
    public function createForStudent(Student $student): QrCard
    {
        return DB::transaction(function () use ($student) {

            // Bloquear al estudiante para evitar
            // creaciones concurrentes.
            $lockedStudent = Student::query()
                ->lockForUpdate()
                ->findOrFail($student->id);

            return $lockedStudent->qrCard()
                ->firstOrCreate(
                    [],
                    [
                        'uuid' => Str::uuid()->toString(),
                        'is_active' => true,
                    ]
                );
        });
    }

    /**
     * Obtener el QR del estudiante.
     *
     * Devuelve el registro aunque esté desactivado.
     */
    public function current(Student $student): ?QrCard
    {
        return $student->qrCard()->first();
    }

    /**
     * Activar o desactivar el QR.
     *
     * El UUID nunca cambia.
     */
    public function setActive(QrCard $qrCard, bool $isActive): QrCard
    {
        return DB::transaction(function () use (
            $qrCard,
            $isActive
        ) {
            $card = QrCard::query()
                ->lockForUpdate()
                ->findOrFail($qrCard->id);

            if ($card->is_active !== $isActive) {
                $card->update([
                    'is_active' => $isActive,
                ]);
            }

            return $card->refresh();
        });
    }
}
