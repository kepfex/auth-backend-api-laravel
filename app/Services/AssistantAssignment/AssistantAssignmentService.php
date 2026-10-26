<?php

namespace App\Services\AssistantAssignment;

use App\Models\AssistantAssignment;
use App\Models\Employee;
use App\Models\GradeSection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssistantAssignmentService
{
    private const ASSISTANT_CODE = 'AUXILIAR_EDUCACION';

    public function create(array $data): AssistantAssignment
    {
        return DB::transaction(function () use ($data) {

            // 1. Bloquear empleado.
            // Mantener este orden en los servicios
            // que modifiquen cargos o asignaciones.
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($data['employee_id']);

            $this->validateEmployee($employee);

            // 2. Resolver aula y período académico.
            $gradeSection = GradeSection::query()
                ->with('academicYear')
                ->findOrFail($data['grade_section_id']);

            if (! $gradeSection->is_active) {
                throw ValidationException::withMessages([
                    'grade_section_id' =>
                    'El grado y sección se encuentra inactivo.',
                ]);
            }

            $start = CarbonImmutable::parse(
                $data['start_date']
            )->startOfDay();

            $end = isset($data['end_date'])
                ? CarbonImmutable::parse($data['end_date'])
                ->startOfDay()
                : null;

            $this->validateAcademicPeriod(
                $gradeSection,
                $start,
                $end
            );

            // 3. Proteger contra períodos duplicados.
            $this->validateNoOverlap(
                $employee,
                $gradeSection,
                $start,
                $end
            );

            // 4. Crear la asignación.
            return AssistantAssignment::create([
                'employee_id' => $employee->id,
                'grade_section_id' => $gradeSection->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end?->toDateString(),
                'is_active' => true,
            ])->load([
                'employee.person',
                'gradeSection.academicYear',
                'gradeSection.grade',
                'gradeSection.section',
            ]);
        }, 3);
    }

    private function validateEmployee(Employee $employee): void
    {
        if (! $employee->is_active) {
            throw ValidationException::withMessages([
                'employee_id' =>
                'El empleado se encuentra inactivo.',
            ]);
        }

        if (
            ! $employee->person()->where('is_active', true)->exists()
        ) {
            throw ValidationException::withMessages([
                'employee_id' =>
                'La persona vinculada al empleado está inactiva.',
            ]);
        }

        $isAssistant = $employee->positions()
            ->where('positions.code', self::ASSISTANT_CODE)
            ->exists();

        if (! $isAssistant) {
            throw ValidationException::withMessages([
                'employee_id' =>
                'El empleado no tiene asignado el cargo '
                    . 'Auxiliar de Educación.',
            ]);
        }
    }

    private function validateAcademicPeriod(
        GradeSection $gradeSection,
        CarbonImmutable $start,
        ?CarbonImmutable $end
    ): void {
        $academicYear = $gradeSection->academicYear;

        if (! $academicYear) {
            throw ValidationException::withMessages([
                'grade_section_id' =>
                'El aula no tiene un año académico válido.',
            ]);
        }

        $yearStart = CarbonImmutable::parse(
            $academicYear->start_date
        )->startOfDay();

        $yearEnd = CarbonImmutable::parse(
            $academicYear->end_date
        )->startOfDay();

        if ($start->lt($yearStart) || $start->gt($yearEnd)) {
            throw ValidationException::withMessages([
                'start_date' =>
                'La fecha de inicio debe estar dentro '
                    . 'del año académico del aula.',
            ]);
        }

        if ($end && ($end->lt($start) || $end->gt($yearEnd))) {
            throw ValidationException::withMessages([
                'end_date' =>
                'La fecha de fin debe estar entre el '
                    . 'inicio de la asignación y el fin '
                    . 'del año académico.',
            ]);
        }
    }

    private function validateNoOverlap(
        Employee $employee,
        GradeSection $gradeSection,
        CarbonImmutable $start,
        ?CarbonImmutable $end
    ): void {
        $startDate = $start->toDateString();

        // NULL representa un intervalo sin fecha final.
        // Se usa la fecha máxima de MySQL para comparar
        // los extremos superiores.
        $endDate = $end?->toDateString() ?? '9999-12-31';

        $overlap = AssistantAssignment::query()
            ->where('employee_id', $employee->id)
            ->where('grade_section_id', $gradeSection->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $startDate);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'grade_section_id' =>
                'El auxiliar ya tiene una asignación '
                    . 'habilitada para esta aula durante '
                    . 'un período coincidente.',
            ]);
        }
    }
}
