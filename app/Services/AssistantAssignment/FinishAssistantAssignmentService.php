<?php

namespace App\Services\AssistantAssignment;

use App\Models\AssistantAssignment;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FinishAssistantAssignmentService
{
    public function finish(
        AssistantAssignment $assignment,
        string $endDate
    ): AssistantAssignment {
        return DB::transaction(function () use (
            $assignment,
            $endDate
        ): AssistantAssignment {

            // 1. Bloquear primero al empleado.
            // Mantenemos el orden empleado -> asignación.
            Employee::query()
                ->lockForUpdate()
                ->findOrFail($assignment->employee_id);

            // 2. Obtener y bloquear la asignación actual.
            $assignment = AssistantAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            // 3. Validar su estado.
            if (! $assignment->is_active) {
                throw ValidationException::withMessages([
                    'assignment' =>
                        'No puedes finalizar una asignación anulada.',
                ]);
            }

            $start = CarbonImmutable::parse(
                $assignment->start_date
            )->startOfDay();

            $requestedEnd = CarbonImmutable::parse(
                $endDate
            )->startOfDay();

            $today = CarbonImmutable::today();

            // 4. La finalización no puede ocurrir
            // antes del inicio real de la asignación.
            if ($requestedEnd->lt($start)) {
                throw ValidationException::withMessages([
                    'end_date' =>
                        'La fecha de finalización no puede '
                        . 'ser anterior a la fecha de inicio.',
                ]);
            }

            if ($requestedEnd->gt($today)) {
                throw ValidationException::withMessages([
                    'end_date' =>
                        'No puedes registrar una finalización futura.',
                ]);
            }

            // 5. Comprobar la fecha final ya registrada.
            if ($assignment->end_date !== null) {
                $currentEnd = CarbonImmutable::parse(
                    $assignment->end_date
                )->startOfDay();

                // Ya terminó anteriormente.
                if ($currentEnd->lt($today)) {
                    throw ValidationException::withMessages([
                        'assignment' =>
                            'Esta asignación ya había finalizado.',
                    ]);
                }

                // Nunca ampliar un período mediante finish().
                if ($requestedEnd->gt($currentEnd)) {
                    throw ValidationException::withMessages([
                        'end_date' =>
                            'La finalización no puede ser posterior '
                            . 'a la fecha de fin registrada.',
                    ]);
                }
            }

            // 6. Establecer el cierre sin anular el historial.
            $assignment->update([
                'end_date' => $requestedEnd->toDateString(),
            ]);

            // Conservamos is_active = true.

            return $assignment->refresh()->load([
                'employee.person',
                'employee.positions',
                'gradeSection.academicYear',
                'gradeSection.grade',
                'gradeSection.section',
            ]);
        }, 3);
    }
}
