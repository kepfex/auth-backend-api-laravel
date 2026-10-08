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
        Schema::table('attendance_marks', function (Blueprint $table) {
            $table->foreignId(
                'qr_card_id'
            )
                ->nullable()
                ->constrained('qr_cards')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(
                'qr_card_id',
                'attendance_marks_qr_card_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_marks', function (Blueprint $table) {
            $table->dropForeign([
                'qr_card_id',
            ]);

            $table->dropIndex(
                'attendance_marks_qr_card_index'
            );

            $table->dropColumn(
                'qr_card_id'
            );
        });
    }
};
