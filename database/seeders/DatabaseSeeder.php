<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Kevin Espinoza',
            'email' => 'kevin.test@example.com',
            'password' => bcrypt('123456'),
        ]);

        $this->call([
            AcademicYearSeeder::class,
            
        ]);
    }
}
