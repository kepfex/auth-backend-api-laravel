<?php

use App\Enums\AttendanceJustificationStatus;
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
        Schema::create('attendance_justifications', function (Blueprint $table) {
            $table->id();

            /*
                |--------------------------------------------------------------------------
                | Día de asistencia
                |--------------------------------------------------------------------------
                |
                | Siempre existe.
                |
                | Incluso cuando justificamos una marcación concreta,
                | mantenemos esta referencia para consultar fácilmente
                | todas las justificaciones de una jornada.
                |
                */

            $table->foreignId('attendance_day_id')
                ->constrained('attendance_days')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                |--------------------------------------------------------------------------
                | Marcación específica
                |--------------------------------------------------------------------------
                |
                | NULL:
                | justificación general del día.
                |
                | CON VALOR:
                | justificación de una entrada/salida específica.
                |
                */

            $table->foreignId('attendance_mark_id')
                ->nullable()
                ->constrained('attendance_marks')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                |--------------------------------------------------------------------------
                | Usuario que presenta/registra la justificación
                |--------------------------------------------------------------------------
                |
                | Actualmente puede ser personal administrativo.
                |
                | En el futuro podrá ser también:
                |
                | User → Guardian → Flutter
                | User → Auxiliar
                |
                */

            $table->foreignId('submitted_by_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                |--------------------------------------------------------------------------
                | Motivo
                |--------------------------------------------------------------------------
                */

            $table->text('reason');

            /*
                |--------------------------------------------------------------------------
                | Archivo de sustento
                |--------------------------------------------------------------------------
                |
                | Ejemplo:
                |
                | attendance-justifications/2026/abc123.pdf
                |
                | El archivo NO irá en public.
                | Lo trabajaremos en 7C-2.
                |
                */

            $table->string('attachment_path', 500)
                ->nullable();

            /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                */

            $table->string('status',30)
            ->default(
                AttendanceJustificationStatus::Pending->value
            );

            /*
                |--------------------------------------------------------------------------
                | Revisión
                |--------------------------------------------------------------------------
                */

            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->text('review_comment')->nullable();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

            $table->index(
                [
                    'attendance_day_id',
                    'status',
                ],
                'attendance_justifications_day_status_index'
            );

            $table->index(
                [
                    'attendance_mark_id',
                    'status',
                ],
                'attendance_justifications_mark_status_index'
            );

            $table->index(
                [
                    'submitted_by_user_id',
                    'created_at',
                ],
                'attendance_justifications_submitter_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_justifications');
    }
};
