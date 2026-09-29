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
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('guardian_id')
                ->constrained('guardians')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('relationship', [
                'padre',
                'madre',
                'abuelo',
                'abuela',
                'tío',
                'tía',
                'hermano/a',
                'tutor_legal',
                'otro',
            ]);

            $table->boolean('is_primary')->default(false);

            $table->boolean('receives_notifications')
                ->default(true);

            $table->timestamps();

            $table->unique(
                ['student_id', 'guardian_id'],
                'student_guardian_unique'
            );

            $table->index(['student_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
    }
};
