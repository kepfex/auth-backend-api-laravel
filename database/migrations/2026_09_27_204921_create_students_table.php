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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->unique('uk_student_person')
                ->constrained('persons')
                ->restrictOnDelete();
            $table->string('student_code', 25)->unique('uk_student_code');
            $table->enum('status', ['activo', 'inactivo', 'egresado'])->default('activo');
            $table->timestamps();
            
            // Índice para mejorar búsquedas por estado
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
