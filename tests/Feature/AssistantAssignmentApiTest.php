<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssistantAssignment;
use App\Models\EducationalLevel;
use App\Models\Employee;
use App\Models\Grade;
use App\Models\GradeSection;
use App\Models\Person;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AssistantAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;
    private GradeSection $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        // Fecha fija para que los tests sean reproducibles.
        $this->travelTo(Carbon::parse('2026-10-25 10:00:00'));

        $this->actingAs(User::factory()->create(), 'api');

        $person = Person::create([
            'document_type' => 'DNI',
            'document_number' => '87654321',
            'first_names' => 'Carlos',
            'paternal_surname' => 'Mendoza',
            'maternal_surname' => 'Torres',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'person_id' => $person->id,
            'employee_code' => 'E2600001',
            'is_active' => true,
        ]);

        $position = Position::create([
            'code' => 'AUXILIAR_EDUCACION',
            'name' => 'Auxiliar de Educación',
            'description' => 'Auxiliar',
            'is_active' => true,
        ]);

        $this->employee->positions()->attach($position->id);

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

        $section = Section::create([
            'name' => 'A',
        ]);

        $this->classroom = GradeSection::create([
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'shift' => 'mañana',
            'capacity' => 30,
            'is_active' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->employee->id,
            'grade_section_id' => $this->classroom->id,
            'start_date' => '2026-03-16',
            'end_date' => '2026-12-18',
        ], $overrides);
    }

    private function createAssignment(
        array $overrides = []
    ): AssistantAssignment {
        return AssistantAssignment::create(
            array_merge(
                $this->payload(),
                ['is_active' => true],
                $overrides
            )
        );
    }

    public function test_creates_assignment(): void
    {
        $this->postJson(
            '/api/assistant-assignments',
            $this->payload()
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.employee_id',
                $this->employee->id
            );

        $this->assertDatabaseCount('assistant_assignments', 1);
    }

    public function test_rejects_overlapping_periods(): void
    {
        $this->createAssignment([
            'end_date' => '2026-06-30',
        ]);

        $this->postJson(
            '/api/assistant-assignments',
            $this->payload([
                'start_date' => '2026-05-01',
                'end_date' => '2026-07-31',
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grade_section_id');

        $this->assertDatabaseCount('assistant_assignments', 1);
    }

    public function test_allows_successive_periods(): void
    {
        $this->createAssignment([
            'end_date' => '2026-06-30',
        ]);

        $this->postJson(
            '/api/assistant-assignments',
            $this->payload([
                'start_date' => '2026-07-01',
                'end_date' => '2026-12-18',
            ])
        )->assertCreated();

        $this->assertDatabaseCount('assistant_assignments', 2);
    }

    public function test_rejects_employee_without_assistant_role(): void
    {
        $this->employee->positions()->detach();

        $this->postJson(
            '/api/assistant-assignments',
            $this->payload()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('employee_id');
    }

    public function test_rejects_inactive_employee(): void
    {
        $this->employee->update(['is_active' => false]);

        $this->postJson(
            '/api/assistant-assignments',
            $this->payload()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('employee_id');
    }

    public function test_rejects_inactive_classroom(): void
    {
        $this->classroom->update(['is_active' => false]);

        $this->postJson(
            '/api/assistant-assignments',
            $this->payload()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grade_section_id');
    }

    public function test_rejects_dates_outside_academic_year(): void
    {
        $this->postJson(
            '/api/assistant-assignments',
            $this->payload([
                'start_date' => '2027-01-10',
                'end_date' => '2027-02-10',
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_date');
    }

    public function test_filters_assignments_by_effective_date(): void
    {
        $this->createAssignment([
            'end_date' => '2026-06-30',
        ]);

        $this->getJson(
            '/api/assistant-assignments?effective_on=2026-05-15'
        )
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson(
            '/api/assistant-assignments?effective_on=2026-07-15'
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_finishes_assignment_preserving_history(): void
    {
        $assignment = $this->createAssignment([
            'end_date' => null,
        ]);

        $this->patchJson(
            "/api/assistant-assignments/{$assignment->id}/finish",
            ['end_date' => '2026-06-30']
        )
            ->assertOk()
            ->assertJsonPath('data.end_date', '2026-06-30')
            ->assertJsonPath('data.is_active', true);

        $this->assertSame(
            1,
            AssistantAssignment::effectiveOn('2026-05-15')->count()
        );

        $this->assertSame(
            0,
            AssistantAssignment::effectiveOn('2026-07-15')->count()
        );
    }

    public function test_cannot_remove_assistant_role_with_pending_assignment(): void
    {
        $this->createAssignment();

        $this->putJson(
            "/api/employees/{$this->employee->id}/positions",
            ['position_ids' => []]
        )
            ->assertUnprocessable();

        $this->assertDatabaseCount('employee_positions', 1);
    }

    public function test_can_remove_assistant_role_after_assignment_ends(): void
    {
        $this->createAssignment([
            'end_date' => '2026-06-30',
        ]);

        $this->putJson(
            "/api/employees/{$this->employee->id}/positions",
            ['position_ids' => []]
        )->assertOk();

        $this->assertDatabaseCount('employee_positions', 0);
        $this->assertDatabaseCount('assistant_assignments', 1);
    }

    public function test_cannot_deactivate_employee_with_pending_assignment(): void
    {
        $this->createAssignment([
            'end_date' => null,
        ]);

        $this->patchJson(
            "/api/employees/{$this->employee->id}/status",
            ['is_active' => false]
        )->assertUnprocessable();

        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'is_active' => 1,
        ]);
    }

    public function test_can_deactivate_employee_after_assignment_ends(): void
    {
        $this->createAssignment([
            'end_date' => '2026-06-30',
        ]);

        $this->patchJson(
            "/api/employees/{$this->employee->id}/status",
            ['is_active' => false]
        )->assertOk();

        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'is_active' => 0,
        ]);
    }
}
