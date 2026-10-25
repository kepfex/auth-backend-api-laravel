<?php

namespace App\Services\Employee;

use App\Enums\InstitutionalCodeType;
use App\Models\Employee;
use App\Models\Person;
use App\Services\InstitutionalCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EmployeeRegistrationService
{
    public function __construct(
        private readonly InstitutionalCodeService $codeService
    ) {}

    public function register(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {

            // 1. Resolver la identidad de la persona.
            $person = isset($data['person_id'])
                ? $this->findExistingPerson($data['person_id'])
                : $this->createPerson($data['person']);

            // Rechazar personas inactivas
            if (! $person->is_active) {
                throw ValidationException::withMessages([
                    'person_id' =>
                    'La persona se encuentra inactiva. '
                        . 'Debes regularizar su estado antes de registrarla como empleado.',
                ]);
            }

            // 2. Verificar que no sea empleado.
            if ($person->employee()->exists()) {
                throw ValidationException::withMessages([
                    'person_id' =>
                    'Esta persona ya está registrada como empleado.',
                ]);
            }

            // 3. Generar código institucional.
            $employeeCode = $this->codeService->generate(
                InstitutionalCodeType::Employee
            );

            // 4. Crear el perfil laboral.
            $employee = Employee::create([
                'person_id' => $person->id,
                'employee_code' => $employeeCode,
                'is_active' => true,
            ]);

            // 5. Cargar relaciones para la respuesta.
            return $employee->load([
                'person',
                'positions',
            ]);
        }, 3);
    }

    private function findExistingPerson(int $personId): Person
    {
        return Person::query()
            ->lockForUpdate()
            ->findOrFail($personId);
    }

    private function createPerson(array $data): Person
    {
        // El Form Request ya validó los campos.
        // La condición de persona activa se establece
        // desde el backend al crear un nuevo empleado.
        unset($data['is_active']);

        return Person::create([
            ...$data,
            'is_active' => true,
        ]);
    }
}
