<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPersonRelationshipTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $document): Person
    {
        return Person::create([
            'document_type' => 'DNI',
            'document_number' => $document,
            'first_names' => 'Carlos',
            'paternal_surname' => 'Mendoza',
            'maternal_surname' => 'Torres',
            'email' => 'contacto@example.com',
            'is_active' => true,
        ]);
    }

    public function test_user_without_person_remains_supported(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->fresh()->person_id);
        $this->assertNull($user->person);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_links_one_account_to_person_without_changing_contact_email(): void
    {
        $person = $this->person('12345678');
        $user = User::factory()->create([
            'person_id' => $person->id,
            'email' => 'e2600001@acp.com',
        ]);

        $this->assertTrue($user->person->is($person));
        $this->assertTrue($person->fresh()->user->is($user));
        $this->assertSame('contacto@example.com', $person->fresh()->email);
        $this->assertSame('e2600001@acp.com', $user->fresh()->email);
    }

    public function test_database_rejects_two_accounts_linked_to_same_person(): void
    {
        $person = $this->person('87654321');
        User::factory()->create(['person_id' => $person->id]);

        $this->expectException(QueryException::class);
        User::factory()->create(['person_id' => $person->id]);
    }

    public function test_database_restricts_deleting_person_with_linked_account(): void
    {
        $person = $this->person('11112222');
        User::factory()->create(['person_id' => $person->id]);

        // Force-delete would remove the identity physically, violating the FK.
        $this->expectException(QueryException::class);
        $person->forceDelete();
    }

    public function test_user_is_active_is_cast_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->assertFalse($user->fresh()->is_active);
    }
}
