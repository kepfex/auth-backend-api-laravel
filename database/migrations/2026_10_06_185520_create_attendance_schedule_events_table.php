<?php

use App\Enums\AttendanceScheduleEventType;
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
        // Eventos de horarios de asistencia
        Schema::create('attendance_schedule_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_schedule_id')
                ->constrained('attendance_schedules')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
                 * ISO-8601:
                 *
                 * 1 lunes
                 * ...
                 * 7 domingo
                 */
            $table->unsignedTinyInteger('day_of_week');

            /*
                 * Orden del evento dentro
                 * de ese día.
                 */
            $table->unsignedTinyInteger('sequence');

            $table->enum(
                'event_type',
                AttendanceScheduleEventType::values()
            );

            $table->time('expected_time');

            $table->unsignedSmallInteger('tolerance_minutes')->default(0);

            $table->timestamps();

            /*
                 * No puede haber dos eventos
                 * con la misma posición el
                 * mismo día.
                 */
            $table->unique(
                [
                    'attendance_schedule_id',
                    'day_of_week',
                    'sequence',
                ],
                'schedule_events_sequence_unique'
            );

            /*
                 * Tampoco necesitamos dos
                 * eventos exactamente a la
                 * misma hora.
                 */
            $table->unique(
                [
                    'attendance_schedule_id',
                    'day_of_week',
                    'expected_time',
                ],
                'schedule_events_time_unique'
            );

            $table->index(
                [
                    'attendance_schedule_id',
                    'day_of_week',
                    'expected_time',
                ],
                'schedule_events_lookup_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_schedule_events');
    }
};
