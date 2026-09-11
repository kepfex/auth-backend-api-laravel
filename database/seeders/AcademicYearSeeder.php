<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('academic_years')->insertOrIgnore([
            [
                'name'         => '2026',
                'start_date'   => '2026-03-01',
                'end_date'     => '2026-12-20',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => '2025',
                'start_date'   => '2025-03-01',
                'end_date'     => '2025-12-20',
                'is_active'    => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}
