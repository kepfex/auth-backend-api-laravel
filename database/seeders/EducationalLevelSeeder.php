<?php

namespace Database\Seeders;

use App\Models\EducationalLevel;
use App\Models\Grade;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EducationalLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            [
                'name'  => 'Inicial',
                'order' => 1,
                'grades' => [
                    ['name' => '3 años', 'order' => 1],
                    ['name' => '4 años', 'order' => 2],
                    ['name' => '5 años', 'order' => 3],
                ],
            ],
            [
                'name'  => 'Primaria',
                'order' => 2,
                'grades' => [
                    ['name' => 'Primero', 'order' => 1],
                    ['name' => 'Segundo', 'order' => 2],
                    ['name' => 'Tercero', 'order' => 3],
                    ['name' => 'Cuarto', 'order' => 4],
                    ['name' => 'Quinto', 'order' => 5],
                    ['name' => 'Sexto', 'order' => 6],
                ],
            ],
            [
                'name'  => 'Secundaria',
                'order' => 3,
                'grades' => [
                    ['name' => 'Primero', 'order' => 1],
                    ['name' => 'Segundo', 'order' => 2],
                    ['name' => 'Tercero', 'order' => 3],
                    ['name' => 'Cuarto', 'order' => 4],
                    ['name' => 'Quinto', 'order' => 5],
                ],
            ],
        ];

        foreach ($levels as $levelData) {
            $level = EducationalLevel::firstOrCreate(
                ['name' => $levelData['name']], 
                ['order' => $levelData['order']] 
            );

            foreach ($levelData['grades'] as $gradeData) {
                Grade::firstOrCreate(
                    [
                        'educational_level_id'  => $level->id,
                        'name'                  => $gradeData['name'],
                    ],
                    ['order' => $gradeData['order']]
                );
            }
        }
    }
}
