<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = ['A', 'B', 'C','D','E','Solidaridad', 'Responsabilidad', 'Alegria'];
        foreach ($sections as $sectionName) {
            Section::firstOrCreate(['name' => $sectionName]);
        }
    }
}
