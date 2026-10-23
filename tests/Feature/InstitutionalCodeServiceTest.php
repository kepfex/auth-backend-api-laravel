<?php

namespace Tests\Feature;

use App\Enums\InstitutionalCodeType;
use App\Services\InstitutionalCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InstitutionalCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_sequential_employee_codes(): void
    {
        $service = app(InstitutionalCodeService::class);

        $this->assertSame(
            'E2600001',
            $service->generate(
                InstitutionalCodeType::Employee,
                2026
            )
        );

        $this->assertSame(
            'E2600002',
            $service->generate(
                InstitutionalCodeType::Employee,
                2026
            )
        );
    }

    public function test_sequences_are_independent(): void
    {
        $service = app(InstitutionalCodeService::class);

        $this->assertSame(
            'E2600001',
            $service->generate(
                InstitutionalCodeType::Employee,
                2026
            )
        );

        $this->assertSame(
            'A2600001',
            $service->generate(
                InstitutionalCodeType::Student,
                2026
            )
        );

        $this->assertSame(
            'P2600001',
            $service->generate(
                InstitutionalCodeType::Guardian,
                2026
            )
        );

        $this->assertSame(
            'E2700001',
            $service->generate(
                InstitutionalCodeType::Employee,
                2027
            )
        );
    }

    public function test_sequence_rolls_back_with_failed_registration(): void
    {
        $service = app(InstitutionalCodeService::class);

        try {
            DB::transaction(function () use ($service) {
                $service->generate(
                    InstitutionalCodeType::Employee,
                    2026
                );

                throw new RuntimeException(
                    'Error simulado en registro.'
                );
            });
        } catch (RuntimeException) {
            // Fallo esperado para probar el rollback.
        }

        $this->assertSame(
            'E2600001',
            $service->generate(
                InstitutionalCodeType::Employee,
                2026
            )
        );
    }
}
