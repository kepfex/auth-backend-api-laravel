<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'Principal', 'description' => 'School Principal'],
            ['name' => 'Auxiliar de Educación', 'description' => 'Asistente de aula y asistencia'],
            ['name' => 'Profesor', 'description' => 'Profesor de la asignatura'],
            ['name' => 'Administrativo', 'description' => 'Personal de oficina'],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(['name' => $pos['name']], $pos);
        }
    }
}
