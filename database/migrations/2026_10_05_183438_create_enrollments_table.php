<?php

use App\Enums\EnrollmentStatus;
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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('grade_section_id')
                ->constrained('grade_sections')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('enrollment_date');

            $table->enum(
                'status',
                EnrollmentStatus::values()
            )->default(EnrollmentStatus::Enrolled->value);

            $table->text('observations')->nullable();

            $table->timestamps();

            $table->unique(
                ['student_id', 'academic_year_id'],
                'enrollments_student_year_unique'
            );

            $table->index(
                ['academic_year_id', 'grade_section_id', 'status'],
                'enrollments_year_classroom_status_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
