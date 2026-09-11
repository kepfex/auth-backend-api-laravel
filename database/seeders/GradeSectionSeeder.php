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
        |
        | Inicial: 3, 4 y 5 años
        | Primaria: 1° a 6° grado
        | Secundaria:
        |   1° -> A-E
        |   2° -> A-E
        |   3° -> A-F
        |   4° -> A-E
        |   5° -> A-E
        |
        */

        $gradeSections = [
            // Inicial
            '3 años' => ['Solidaridad'],
            '4 años' => ['Responsabilidad'],
            '5 años' => ['Alegria'],

            // Primaria
            'Primero' => ['A', 'B', 'C'],
            'Segundo' => ['A', 'B', 'C'],
            'Tercero' => ['A', 'B', 'C'],
            'Cuarto' => ['A', 'B', 'C'],
            'Quinto' => ['A', 'B', 'C'],
            'Sexto' => ['A', 'B', 'C'],

            // Secundaria
        ];

        foreach ($gradeSections as $gradeName => $sections) {

            // Buscar el grado
            $grade = Grade::where('name', $gradeName)->first();

            if (!$grade) {
                $this->command->warn(
                    "No se encontró el grado: {$gradeName}"
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
