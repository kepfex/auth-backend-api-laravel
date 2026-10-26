<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EmployeePositionService
{
    private const ASSISTANT_CODE = 'AUXILIAR_EDUCACION';

    public function __construct(
        private readonly EmployeePositionValidator $positionValidator
    ) {}

    /**
     * Reemplaza los cargos asociados al empleado.
     *
     * @param array<int, int> $positionIds
     */
    public function sync(
        Employee $employee,
        array $positionIds
    ): Employee {
        return DB::transaction(function () use (
            $employee,
            $positionIds
        ): Employee {
            // 1. Bloquear empleado.
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if (!$employee->is_active) {
                throw ValidationException::withMessages([
                    'employee' =>
                        'No puedes modificar los cargos de un empleado inactivo.',
                ]);
            }

            // 2. Verificar los cargos solicitados.
            $this->positionValidator
                ->validateActive($positionIds);

            // 3. Buscar el cargo técnico de auxiliar.
            $assistantPosition = Position::query()
                ->where('code', self::ASSISTANT_CODE)
                ->first();

            if (!$assistantPosition) {
                throw ValidationException::withMessages([
                    'position_ids' =>
                        'No está configurado el cargo Auxiliar de Educación.',
                ]);
            }

            // 4. Verificar si actualmente es auxiliar.
            $hasAssistantPosition = $employee
                ->positions()
                ->whereKey($assistantPosition->id)
                ->exists();

            // 5. Comprobar si conservará el cargo.
            $keepsAssistantPosition = in_array(
                $assistantPosition->id,
                $positionIds,
                true
            );

            // 6. Proteger asignaciones escolares.
            if (
                $hasAssistantPosition &&
                !$keepsAssistantPosition
            ) {
                $hasEnabledAssignments = $employee
                    ->assistantAssignments()
                    ->where('is_active', true)
                    ->exists();

                if ($hasEnabledAssignments) {
                    throw ValidationException::withMessages([
                        'position_ids' =>
                            'No puedes retirar el cargo Auxiliar de Educación ' .
                            'porque el empleado tiene asignaciones escolares ' .
                            'habilitadas. Finaliza esas asignaciones primero.',
                    ]);
                }
            }

            // 7. Sincronizar la relación N:M.
            $employee->positions()->sync($positionIds);

            // 8. Devolver estado actualizado.
            return $employee->load([
                'person',
                'positions',
            ]);
        }, 3);
    }
}
