<?php

use App\Enums\QrScanResult;
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
        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();

            /*
                |--------------------------------------------------------------------------
                | Puede ser NULL
                |--------------------------------------------------------------------------
                |
                | Si alguien escanea:
                |
                | "ABCDEFG123"
                |
                | y no corresponde a ninguna QrCard,
                | no existe una FK posible.
                |
                */

            $table->foreignId('qr_card_id')
                ->nullable()
                ->constrained('qr_cards')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                |--------------------------------------------------------------------------
                | Marcación generada
                |--------------------------------------------------------------------------
                |
                | Solo tendrá valor cuando el scan
                | termine creando una AttendanceMark.
                |
                */

            $table->foreignId('attendance_mark_id')
                ->nullable()
                ->constrained('attendance_marks')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                |--------------------------------------------------------------------------
                | Fingerprint
                |--------------------------------------------------------------------------
                |
                | NO guardamos el código inválido en texto plano.
                |
                | SHA-256(token)
                |
                */

            $table->char('token_fingerprint', 64)
                ->nullable();

            $table->timestamp('scanned_at');

            $table->string('result', 30)
                ->default(QrScanResult::Rejected->value);

            /*
                |--------------------------------------------------------------------------
                | Código/motivo técnico
                |--------------------------------------------------------------------------
                |
                | Ejemplos futuros:
                |
                | unknown_card
                | revoked_card
                | expired_card
                | duplicate_mark
                | no_schedule
                | no_active_enrollment
                | outside_window
                |
                */

            $table->string('reason', 100)
                ->nullable();

            $table->timestamps();

            $table->index(
                [
                    'qr_card_id',
                    'scanned_at',
                ],
                'qr_scans_card_date_index'
            );

            $table->index(
                [
                    'result',
                    'scanned_at',
                ],
                'qr_scans_result_date_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_scans');
    }
};
