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
        Schema::create('qr_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
                |--------------------------------------------------------------------------
                | Valor almacenado físicamente dentro del QR
                |--------------------------------------------------------------------------
                |
                | No contiene:
                |
                | DNI
                | student_id
                | nombre
                |
                | Solamente un identificador opaco.
                |
                */

            $table->uuid('uuid')
                ->unique();

            /*
                |--------------------------------------------------------------------------
                | Emisión
                |--------------------------------------------------------------------------
                */

            $table->timestamp('issued_at');

            $table->foreignId(
                'issued_by_user_id'
            )
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
                |--------------------------------------------------------------------------
                | Expiración opcional
                |--------------------------------------------------------------------------
                |
                | NULL significa:
                |
                | la credencial no expira automáticamente.
                |
                */

            $table->timestamp('expires_at')->nullable();

            /*
                |--------------------------------------------------------------------------
                | Revocación
                |--------------------------------------------------------------------------
                */

            $table->timestamp(
                'revoked_at'
            )->nullable();

            $table->foreignId('revoked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('revocation_reason',500)->nullable();

            $table->timestamps();

            /*
                |--------------------------------------------------------------------------
                | Buscar tarjeta utilizable del estudiante
                |--------------------------------------------------------------------------
                */

            $table->index(
                [
                    'student_id',
                    'revoked_at',
                    'expires_at',
                ],
                'qr_cards_student_status_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_cards');
    }
};
