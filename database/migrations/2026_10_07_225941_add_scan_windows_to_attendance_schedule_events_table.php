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
        Schema::table('attendance_schedule_events', function (Blueprint $table) {
            /*
                |--------------------------------------------------------------------------
                | Ventana anterior
                |--------------------------------------------------------------------------
                |
                | Ejemplo:
                |
                | expected_time = 08:00
                | window_before_minutes = 60
                |
                | puede empezar a marcar desde 07:00
                |
                */

            $table->unsignedSmallInteger(
                'window_before_minutes'
            )
                ->default(60)
                ->after('tolerance_minutes');

            /*
                |--------------------------------------------------------------------------
                | Ventana posterior
                |--------------------------------------------------------------------------
                |
                | Ejemplo:
                |
                | expected_time = 08:00
                | window_after_minutes = 60
                |
                | puede marcar hasta 09:00
                |
                */

            $table->unsignedSmallInteger(
                'window_after_minutes'
            )
                ->default(60)
                ->after('window_before_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_schedule_events', function (Blueprint $table) {
            $table->dropColumn([
                'window_before_minutes',
                'window_after_minutes',
            ]);
        });
    }
};
