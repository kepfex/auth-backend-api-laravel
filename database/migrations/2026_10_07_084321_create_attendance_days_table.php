<?php

use App\Enums\AttendanceDayStatus;
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
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')
                ->constrained('enrollments')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                 * Horario que fue aplicado
                 * concretamente ese día.
                 */
            $table->foreignId('attendance_schedule_id')
                ->constrained('attendance_schedules')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('date');

            /*
                 * Usamos string + PHP Enum
                 * en lugar de ENUM SQL.
                 */
            $table->string('status', 30)
                ->default(
                    AttendanceDayStatus::Pending->value
                );

            $table->text('observation')->nullable();

            $table->timestamps();

            /*
                 * Un estudiante matriculado
                 * solo tiene un AttendanceDay
                 * por fecha.
                 */
            $table->unique(
                [
                    'enrollment_id',
                    'date',
                ],
                'attendance_days_enrollment_date_unique'
            );

            /*
                 * Consultas por horario y fecha.
                 */
            $table->index(
                [
                    'attendance_schedule_id',
                    'date',
                ],
                'attendance_days_schedule_date_index'
            );

            /*
                 * Reportes diarios.
                 */
            $table->index(
                [
                    'date',
                    'status',
                ],
                'attendance_days_date_status_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};
