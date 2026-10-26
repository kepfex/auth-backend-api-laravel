<?php

namespace App\Services\Employee;

use App\Models\Position;
use Illuminate\Validation\ValidationException;

final class EmployeePositionValidator
{
    /**
     * Verifica que todos los cargos existan
     * y estén habilitados.
     *
     * @param array<int, int> $positionIds
     */
    public function validateActive(array $positionIds): void
    {
        if (count($positionIds) !== count(array_unique($positionIds))) {
            throw ValidationException::withMessages([
                'position_ids' => 'No se permiten cargos duplicados.',
            ]);
        }

        if ($positionIds === []) {
            return;
        }

        $count = Position::query()
            ->whereIn('id', $positionIds)
            ->where('is_active', true)
            ->count();

        if ($count !== count($positionIds)) {
            throw ValidationException::withMessages([
                'position_ids' =>
                'Uno o más cargos no existen o están deshabilitados.',
            ]);
        }
    }
}
