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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educational_level_id')
                ->constrained('educational_levels')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 50);
            $table->unsignedTinyInteger('order');
            $table->timestamps();

            $table->unique(['educational_level_id', 'name']);
            $table->unique(['educational_level_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
