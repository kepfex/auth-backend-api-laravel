<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Horarios de asistencia
        Schema::create('attendance_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('educational_level_id')
                ->constrained('educational_levels')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                 * NULL:
                 * horario general del nivel.
                 *
                 * CON VALOR:
                 * horario particular de esa aula.
                 */
            $table->foreignId('grade_section_id')
                ->nullable()
                ->constrained('grade_sections')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('name',150);

            $table->date('valid_from');

            $table->date('valid_until');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            /*
                 * Consultas principales del
                 * ScheduleResolver futuro.
                 */
            $table->index(
                [
                    'academic_year_id',
                    'educational_level_id',
                    'is_active',
                ],
                'attendance_schedules_level_index'
            );

            $table->index(
                [
                    'academic_year_id',
                    'grade_section_id',
                    'is_active',
                ],
                'attendance_schedules_classroom_index'
            );

            $table->index(
                [
                    'valid_from',
                    'valid_until',
                ],
                'attendance_schedules_validity_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_schedules');
    }
};
