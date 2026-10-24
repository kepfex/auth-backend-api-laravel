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
        Schema::create('assistant_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('grade_section_id')
                ->constrained('grade_sections')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['employee_id', 'is_active', 'start_date', 'end_date'],
                'assistant_assignments_employee_lookup'
            );

            $table->index(
                ['grade_section_id', 'is_active', 'start_date', 'end_date'],
                'assistant_assignments_classroom_lookup'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_assignments');
    }
};
