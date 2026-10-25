<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Person;
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

    public function test_registers_employee_from_existing_person(): void
    {
        $person = $this->createPerson();

        $response = $this->postJson('/api/employees', [
            'person_id' => $person->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.employee_code',
                'E2600001'
            )
            ->assertJsonPath(
                'data.person_id',
                $person->id
            )
            ->assertJsonPath(
                'data.is_active',
                true
            );

        $this->assertDatabaseHas('employees', [
            'person_id' => $person->id,
            'employee_code' => 'E2600001',
            'is_active' => 1,
        ]);

        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_registers_employee_with_new_person(): void
    {
        $response = $this->postJson('/api/employees', [
            'person' => [
                'document_type' => 'DNI',
                'document_number' => '87654321',
                'first_names' => 'Ana María',
                'paternal_surname' => 'Torres',
                'maternal_surname' => 'Pérez',
                'sex' => 'F',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.employee_code',
                'E2600001'
            );

        $person = Person::query()
            ->where('document_number', '87654321')
            ->firstOrFail();

        $this->assertDatabaseHas('employees', [
            'person_id' => $person->id,
            'employee_code' => 'E2600001',
        ]);

        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_cannot_register_same_person_twice(): void
    {
        $person = $this->createPerson();

        $this->postJson('/api/employees', [
            'person_id' => $person->id,
        ])->assertCreated();

        $this->postJson('/api/employees', [
            'person_id' => $person->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('person_id');

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_rejects_duplicate_document(): void
    {
        $this->createPerson('12345678');

        $response = $this->postJson('/api/employees', [
            'person' => [
                'document_type' => 'DNI',
                'document_number' => '12345678',
                'first_names' => 'Otra Persona',
                'paternal_surname' => 'Prueba',
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'person.document_number'
            );

        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('employees', 0);
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

    public function test_requires_authentication(): void
    {
        app('auth')->forgetGuards();

        $this->getJson('/api/employees')
            ->assertUnauthorized();
    }
}
