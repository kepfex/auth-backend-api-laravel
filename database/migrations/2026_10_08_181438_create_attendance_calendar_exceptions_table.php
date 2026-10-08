<?php

use App\Enums\AttendanceCalendarExceptionType;
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
        Schema::create('attendance_calendar_exceptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                 * NULL:
                 * excepción institucional.
                 */
            $table->foreignId('educational_level_id')
                ->nullable()
                ->constrained('educational_levels')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                 * NULL:
                 * aplica al nivel o institución.
                 *
                 * CON VALOR:
                 * excepción exclusiva del aula.
                 */
            $table->foreignId('grade_section_id')
                ->nullable()
                ->constrained('grade_sections')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                 * Solamente se utiliza para:
                 *
                 * type = schedule_override
                 */
            $table->foreignId('attendance_schedule_id')
                ->nullable()
                ->constrained('attendance_schedules')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('date');

            $table->string('type', 30)
                ->default(AttendanceCalendarExceptionType::NonWorking->value);

            $table->string('name', 150);

            $table->text('reason')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index(
                [
                    'academic_year_id',
                    'date',
                    'is_active',
                ],
                'attendance_calendar_exception_date_index'
            );

            $table->index(
                [
                    'educational_level_id',
                    'grade_section_id',
                    'date',
                ],
                'attendance_calendar_exception_scope_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_calendar_exceptions');
    }
};
