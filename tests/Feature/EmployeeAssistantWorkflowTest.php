<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationalLevel;
use App\Models\Grade;
use App\Models\GradeSection;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeAssistantWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Position $assistantPosition;
    private GradeSection $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-25 10:00:00'));
        $this->actingAs(User::factory()->create(), 'api');

        $this->assistantPosition = Position::create([
            'code' => 'AUXILIAR_EDUCACION',
            'name' => 'Auxiliar de Educación',
            'description' => 'Apoyo escolar',
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $level = EducationalLevel::create([
            'code' => 'SEC',
            'name' => 'Secundaria',
            'order' => 3,
        ]);

        $grade = Grade::create([
            'educational_level_id' => $level->id,
            'code' => 'SEC_1',
            'name' => 'Primero',
            'order' => 1,
        ]);

        $section = Section::create(['name' => 'A']);

        $this->classroom = GradeSection::create([
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'shift' => 'mañana',
            'capacity' => 30,
            'is_active' => true,
        ]);
    }

    private function registerEmployee(): int
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
            'position_ids' => [$this->assistantPosition->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.employee_code', 'E2600001')
            ->assertJsonCount(1, 'data.positions');

        return (int) $response->json('data.id');
    }

    private function assignClassroom(int $employeeId): int
    {
        $response = $this->postJson('/api/assistant-assignments', [
            'employee_id' => $employeeId,
            'grade_section_id' => $this->classroom->id,
            'start_date' => '2026-03-16',
            'end_date' => null,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_active', true);

        return (int) $response->json('data.id');
    }

    public function test_complete_employee_to_finished_assignment_workflow(): void
    {
        $employeeId = $this->registerEmployee();
        $assignmentId = $this->assignClassroom($employeeId);

        // El auxiliar figura como responsable antes del cierre.
        $this->getJson('/api/assistant-assignments?' . http_build_query([
            'employee_id' => $employeeId,
            'effective_on' => '2026-05-15',
        ]))->assertOk()->assertJsonCount(1, 'data');

        // Registrar cierre anticipado; mantiene historial habilitado.
        $this->patchJson("/api/assistant-assignments/{$assignmentId}/finish", [
            'end_date' => '2026-06-30',
        ])->assertOk()
            ->assertJsonPath('data.end_date', '2026-06-30')
            ->assertJsonPath('data.is_active', true);

        $this->getJson('/api/assistant-assignments?' . http_build_query([
            'employee_id' => $employeeId,
            'effective_on' => '2026-05-15',
        ]))->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/assistant-assignments?' . http_build_query([
            'employee_id' => $employeeId,
            'effective_on' => '2026-07-15',
        ]))->assertOk()->assertJsonCount(0, 'data');

        // Aún existe en el listado histórico sin effective_on.
        $this->getJson('/api/assistant-assignments?' . http_build_query([
            'employee_id' => $employeeId,
        ]))->assertOk()->assertJsonCount(1, 'data');

        // El cierre histórico permite retirar el cargo.
        $this->putJson("/api/employees/{$employeeId}/positions", [
            'position_ids' => [],
        ])->assertOk()->assertJsonCount(0, 'data.positions');

        // Y después desactivar al empleado sin borrar el historial.
        $this->patchJson("/api/employees/{$employeeId}/status", [
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('assistant_assignments', [
            'id' => $assignmentId,
            'employee_id' => $employeeId,
            'end_date' => '2026-06-30',
            'is_active' => 1,
        ]);
        $this->assertDatabaseCount('employee_positions', 0);
        $this->assertDatabaseHas('employees', [
            'id' => $employeeId,
            'is_active' => 0,
        ]);
    }

    public function test_pending_assignment_blocks_position_removal_and_deactivation(): void
    {
        $employeeId = $this->registerEmployee();
        $assignmentId = $this->assignClassroom($employeeId);

        $this->putJson("/api/employees/{$employeeId}/positions", [
            'position_ids' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('position_ids');

        $this->patchJson("/api/employees/{$employeeId}/status", [
            'is_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('is_active');

        $this->assertDatabaseHas('employee_positions', [
            'employee_id' => $employeeId,
            'position_id' => $this->assistantPosition->id,
        ]);
        $this->assertDatabaseHas('assistant_assignments', [
            'id' => $assignmentId,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employeeId,
            'is_active' => 1,
        ]);
    }
}
