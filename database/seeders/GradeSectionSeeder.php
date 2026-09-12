<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeSection;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GradeSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el año académico activo
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (!$academicYear) {
            $this->command->warn(
                'No existe un año académico activo. GradeSectionSeeder no se ejecutó.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Configuración de grados y secciones
        |--------------------------------------------------------------------------
        */

        $gradeSections = [
            // Inicial
            'INI-03' => ['Solidaridad'],
            'INI-04' => ['Responsabilidad'],
            'INI-05' => ['Alegria'],

            // Primaria
            'PRI-01' => ['A', 'B', 'C'],
            'PRI-02' => ['A', 'B', 'C'],
            'PRI-03' => ['A', 'B', 'C'],
            'PRI-04' => ['A', 'B', 'C'],
            'PRI-05' => ['A', 'B', 'C'],
            'PRI-06' => ['A', 'B', 'C'],

            // Secundaria
            'SEC-01' => ['A', 'B', 'C', 'D', 'E'],
            'SEC-02' => ['A', 'B', 'C', 'D', 'E'],
            'SEC-03' => ['A', 'B', 'C', 'D', 'E'],
            'SEC-04' => ['A', 'B', 'C', 'D', 'E'],
            'SEC-05' => ['A', 'B', 'C', 'D', 'E'],
        ];

        foreach ($gradeSections as $gradeCode => $sections) {

            // Buscar el grado
            $grade = Grade::where('code', $gradeCode)->first();

            if (!$grade) {
                $this->command->warn(
                    "No se encontró el grado: {$gradeCode}"
                );

                continue;
            }

            foreach ($sections as $sectionName) {

                // Buscar la sección
                $section = Section::where('name', $sectionName)->first();

                if (!$section) {
                    $this->command->warn(
                        "No se encontró la sección: {$sectionName}"
                    );

                    continue;
                }

                GradeSection::firstOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'grade_id'         => $grade->id,
                        'section_id'       => $section->id,
                    ],
                    [
                        'shift'      => 'mañana',
                        'capacity'   => 30,
                        'is_active'  => true,
                    ]
                );
            }
        }

        $this->command->info(
            "Grados-sección creados para el año académico: {$academicYear->name}"
        );
    }
}
