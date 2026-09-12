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
                'code'  => 'INI',
                'name'  => 'Inicial',
                'order' => 1,
                'grades' => [
                    ['code'  => 'INI-03', 'name' => '3 años', 'order' => 1],
                    ['code'  => 'INI-04', 'name' => '4 años', 'order' => 2],
                    ['code'  => 'INI-05', 'name' => '5 años', 'order' => 3],
                ],
            ],
            [
                'code'  => 'PRI',
                'name'  => 'Primaria',
                'order' => 2,
                'grades' => [
                    ['code'  => 'PRI-01', 'name' => 'Primero', 'order' => 1],
                    ['code'  => 'PRI-02', 'name' => 'Segundo', 'order' => 2],
                    ['code'  => 'PRI-03', 'name' => 'Tercero', 'order' => 3],
                    ['code'  => 'PRI-04', 'name' => 'Cuarto', 'order' => 4],
                    ['code'  => 'PRI-05', 'name' => 'Quinto', 'order' => 5],
                    ['code'  => 'PRI-06', 'name' => 'Sexto', 'order' => 6],
                ],
            ],
            [
                'code'  => 'SEC',
                'name'  => 'Secundaria',
                'order' => 3,
                'grades' => [
                    ['code'  => 'SEC-01', 'name' => 'Primero', 'order' => 1],
                    ['code'  => 'SEC-02', 'name' => 'Segundo', 'order' => 2],
                    ['code'  => 'SEC-03', 'name' => 'Tercero', 'order' => 3],
                    ['code'  => 'SEC-04', 'name' => 'Cuarto', 'order' => 4],
                    ['code'  => 'SEC-05', 'name' => 'Quinto', 'order' => 5],
                ],
            ],
        ];

        foreach ($levels as $levelData) {
            $level = EducationalLevel::updateOrCreate(
                [
                    'code' => $levelData['code'],
                ],
                [
                    'name' => $levelData['name'],
                    'order' => $levelData['order'],
                ]
            );

            foreach ($levelData['grades'] as $gradeData) {
                Grade::updateOrCreate(
                    [
                        'code' => $gradeData['code'],
                    ],
                    [
                        'educational_level_id' => $level->id,
                        'name' => $gradeData['name'],
                        'order' => $gradeData['order'],
                    ]
                );
            }
        }
    }
}
