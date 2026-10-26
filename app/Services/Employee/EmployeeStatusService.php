<?php

namespace App\Services\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EmployeeStatusService
{
    public function setActive(Employee $employee, bool $active): Employee
    {
        return DB::transaction(function () use (
            $employee,
            $active
        ): Employee {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if ($employee->is_active === $active) {
                return $employee->load(['person', 'positions']);
            }

            if (!$active) {

                $today = now()->toDateString();

                $hasPendingAssignments = $employee
                    ->assistantAssignments()
                    ->where('is_active', true)
                    ->where(function ($query) use ($today) {
                        $query
                            ->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $today);
                    })
                    ->exists();

                if ($hasPendingAssignments) {
                    throw ValidationException::withMessages([
                        'is_active' =>
                        'El empleado tiene asignaciones vigentes '
                            . 'o futuras. Debes regularizarlas '
                            . 'antes de desactivar su perfil laboral.',
                    ]);
                }
            }

            $employee->update([
                'is_active' => $active,
            ]);

            return $employee->refresh()->load([
                'person',
                'positions',
            ]);
        }, 3);
    }
}
