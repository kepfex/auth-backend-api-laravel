<?php

namespace Tests\Feature;

use App\Enums\InstitutionalCodeType;
use App\Models\Employee;
use App\Services\Employee\EmployeeRegistrationService;
use App\Services\InstitutionalCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EmployeeRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_failure_rolls_back_person_and_code(): void
    {
        $this->travelTo(
            \Illuminate\Support\Carbon::parse('2026-10-25')
        );

        // Simular un fallo después de generar
        // el código y antes de guardar Employee.
        Employee::creating(function () {
            throw new RuntimeException(
                'Fallo simulado al crear empleado.'
            );
        });

        try {
            app(EmployeeRegistrationService::class)
                ->register([
                    'person' => [
                        'document_type' => 'DNI',
                        'document_number' => '87654321',
                        'first_names' => 'Ana María',
                        'paternal_surname' => 'Torres',
                        'maternal_surname' => 'Pérez',
                    ],
                ]);

            $this->fail(
                'Se esperaba una excepción durante el registro.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Fallo simulado al crear empleado.',
                $exception->getMessage()
            );
        }

        // No debe persistir la identidad incompleta.
        $this->assertDatabaseCount('persons', 0);

        // No debe persistir el empleado.
        $this->assertDatabaseCount('employees', 0);

        // El siguiente código sigue siendo E2600001.
        $code = app(InstitutionalCodeService::class)
            ->generate(
                InstitutionalCodeType::Employee,
                2026
            );

        $this->assertSame('E2600001', $code);
    }
}
