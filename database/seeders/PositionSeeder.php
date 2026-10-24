<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['code' => 'DIRECTOR', 'name' => 'Director', 'description' => 'School Principal'],
            ['code' => 'AUXILIAR_EDUCACION', 'name' => 'Auxiliar de Educación', 'description' => 'Asistente de aula y asistencia'],
            ['code' => 'DOCENTE', 'name' => 'Profesor', 'description' => 'Profesor de la asignatura'],
            ['code' => 'ADMINISTRATIVO', 'name' => 'Administrativo', 'description' => 'Personal de oficina'],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(['name' => $pos['name']], $pos);
        }
    }
}
