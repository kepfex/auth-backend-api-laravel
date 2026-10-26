<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fecha fija para obtener códigos predecibles.
        $this->travelTo(
            \Illuminate\Support\Carbon::parse('2026-10-25 10:00:00')
        );

        // Simular autenticación con el guard de la API.
        $this->actingAs(
            User::factory()->create(),
            'api'
        );
    }

    private function createPerson(
        string $documentNumber = '12345678'
    ): Person {
        return Person::create([
            'document_type' => 'DNI',
            'document_number' => $documentNumber,
            'first_names' => 'Carlos Alberto',
            'paternal_surname' => 'Mendoza',
            'maternal_surname' => 'Torres',
            'is_active' => true,
        ]);
    }

    private function createPosition(
        string $code = 'DOCENTE',
        string $name = 'Profesor'
    ): Position {
        return Position::create([
            'code' => $code,
            'name' => $name,
            'description' => $name,
            'is_active' => true,
        ]);
    }


    public function test_registers_employee_from_existing_person(): void
    {
        $person = $this->createPerson();
        $position = $this->createPosition();

        $response = $this->postJson('/api/employees', [
            'person_id' => $person->id,
            'position_ids' => [$position->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.employee_code', 'E2600001')
            ->assertJsonPath('data.person_id', $person->id)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonCount(1, 'data.positions');

        $employeeId = $response->json('data.id');

        $this->assertDatabaseHas('employees', [
            'id' => $employeeId,
            'person_id' => $person->id,
            'employee_code' => 'E2600001',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('employee_positions', [
            'employee_id' => $employeeId,
            'position_id' => $position->id,
        ]);

        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('employee_positions', 1);
    }



    public function test_registers_employee_with_new_person(): void
    {
        $position = $this->createPosition();

        $response = $this->postJson('/api/employees', [
            'person' => [
                'document_type' => 'DNI',
                'document_number' => '87654321',
                'first_names' => 'Ana María',
                'paternal_surname' => 'Torres',
                'maternal_surname' => 'Pérez',
                'sex' => 'F',
            ],
            'position_ids' => [$position->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.employee_code', 'E2600001')
            ->assertJsonCount(1, 'data.positions');

        $person = Person::query()
            ->where('document_number', '87654321')
            ->firstOrFail();

        $employeeId = $response->json('data.id');

        $this->assertDatabaseHas('employees', [
            'id' => $employeeId,
            'person_id' => $person->id,
            'employee_code' => 'E2600001',
        ]);

        $this->assertDatabaseHas('employee_positions', [
            'employee_id' => $employeeId,
            'position_id' => $position->id,
        ]);

        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('employee_positions', 1);
    }


    public function test_cannot_register_same_person_twice(): void
    {
        // 1. Crear un cargo activo.
        $position = $this->createPosition();

        // 2. Crear una persona.
        $person = $this->createPerson();

        // 3. Primer registro: debe funcionar.
        $this->postJson('/api/employees', [
            'person_id' => $person->id,
            'position_ids' => [$position->id],
        ])->assertCreated();

        // 4. Segundo registro: debe rechazarse.
        $this->postJson('/api/employees', [
            'person_id' => $person->id,
            'position_ids' => [$position->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('person_id');

        // 5. Verificar que no se duplicaron registros.
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('employee_positions', 1);
    }



    public function test_rejects_duplicate_document(): void
    {
        $this->createPerson('12345678');
        $position = $this->createPosition();

        $response = $this->postJson('/api/employees', [
            'person' => [
                'document_type' => 'DNI',
                'document_number' => '12345678',
                'first_names' => 'Otra Persona',
                'paternal_surname' => 'Prueba',
                'maternal_surname' => 'Ejemplo',
            ],
            'position_ids' => [$position->id],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'person.document_number'
            );

        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('employee_positions', 0);
    }


    public function test_lists_and_filters_employees(): void
    {
        $person = $this->createPerson();

        Employee::create([
            'person_id' => $person->id,
            'employee_code' => 'E2600001',
            'is_active' => true,
        ]);

        $this->getJson(
            '/api/employees?search=12345678&per_page=10'
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.employee_code',
                'E2600001'
            )
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);

        $this->getJson(
            '/api/employees?search=NO_EXISTE'
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_shows_employee_details(): void
    {
        $person = $this->createPerson();

        $employee = Employee::create([
            'person_id' => $person->id,
            'employee_code' => 'E2600001',
            'is_active' => true,
        ]);

        $this->getJson("/api/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $employee->id
            )
            ->assertJsonPath(
                'data.person_id',
                $person->id
            )
            ->assertJsonPath(
                'data.employee_code',
                'E2600001'
            )
            ->assertJsonStructure([
                'data' => [
                    'person',
                    'positions',
                ],
            ]);
    }


    public function test_registers_employee_with_multiple_positions(): void
    {
        $person = $this->createPerson();

        $assistant = Position::create([
            'code' => 'AUXILIAR_EDUCACION',
            'name' => 'Auxiliar de Educación',
            'description' => 'Asistencia escolar',
            'is_active' => true,
        ]);

        $teacher = Position::create([
            'code' => 'DOCENTE',
            'name' => 'Profesor',
            'description' => 'Docencia',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/employees', [
            'person_id' => $person->id,
            'position_ids' => [
                $assistant->id,
                $teacher->id,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonCount(2, 'data.positions');

        $employeeId = $response->json('data.id');

        $this->assertDatabaseHas('employee_positions', [
            'employee_id' => $employeeId,
            'position_id' => $assistant->id,
        ]);

        $this->assertDatabaseHas('employee_positions', [
            'employee_id' => $employeeId,
            'position_id' => $teacher->id,
        ]);

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('employee_positions', 2);
    }


    public function test_cannot_register_employee_without_positions(): void
    {
        $person = $this->createPerson();

        $this->postJson('/api/employees', [
            'person_id' => $person->id,
            'position_ids' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('position_ids');

        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('employee_positions', 0);
    }


    public function test_requires_authentication(): void
    {
        app('auth')->forgetGuards();

        $this->getJson('/api/employees')
            ->assertUnauthorized();
    }
}
