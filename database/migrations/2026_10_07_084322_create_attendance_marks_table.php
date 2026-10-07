<?php

use App\Enums\AttendanceMarkSource;
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
        Schema::create('attendance_marks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_day_id')
                ->constrained('attendance_days')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
                 * Evento esperado al cual
                 * corresponde esta marcación.
                 *
                 * Puede ser NULL si no pudo
                 * asociarse a ningún evento.
                 */
            $table->foreignId('attendance_schedule_event_id')
                ->nullable()
                ->constrained('attendance_schedule_events')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                 * Guardamos también el tipo
                 * efectivo de marcación.
                 *
                 * Es útil incluso cuando
                 * schedule_event_id es NULL.
                 */
            $table->string('event_type', 20);

            /*
                 * Momento REAL de la marcación.
                 */
            $table->timestamp('recorded_at');

            /*
                 * on_time
                 * late
                 * early
                 * unmatched
                 */
            $table->string('status', 30);

            /*
                 * real - esperado
                 *
                 * Puede ser positivo o negativo.
                 */
            $table->smallInteger('difference_minutes')
                ->nullable();

                /*
                 * manual | qr
                 */
            $table->string('source', 30)
                ->default(
                    AttendanceMarkSource::Manual->value
                );

                /*
                 * Solo tendrá valor cuando
                 * un usuario administrativo
                 * registre/corrija manualmente.
                 */
            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->text('observation')->nullable();

            $table->timestamps();

            /*
                 * Un evento esperado solamente
                 * puede ser satisfecho una vez
                 * dentro del mismo AttendanceDay.
                 *
                 * Si event_id es NULL,
                 * MySQL permite varias filas NULL.
                 */
            $table->unique(
                [
                    'attendance_day_id',
                    'attendance_schedule_event_id',
                ],
                'attendance_marks_day_event_unique'
            );

            /*
                 * Recuperar cronológicamente
                 * las marcas del día.
                 */
            $table->index(
                [
                    'attendance_day_id',
                    'recorded_at',
                ],
                'attendance_marks_day_recorded_index'
            );

            /*
                 * Reportes de tardanzas,
                 * anticipaciones, etc.
                 */
            $table->index(
                [
                    'status',
                    'recorded_at',
                ],
                'attendance_marks_status_recorded_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_marks');
    }
};
