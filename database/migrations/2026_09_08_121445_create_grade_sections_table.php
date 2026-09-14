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
        Schema::create('grade_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('grade_id')
                ->constrained('grades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('section_id')
                ->constrained('sections')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('shift', ['mañana', 'tarde', 'mañana y tarde'])
                ->default('mañana');
            $table->unsignedInteger('capacity')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academic_year_id', 'grade_id', 'section_id', 'shift'], 'uk_academic_grade_section_shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_sections');
    }
};
