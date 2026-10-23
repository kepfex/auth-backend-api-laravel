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
        Schema::create('institutional_code_sequences', function (Blueprint $table) {
            $table->id();
            
            $table->string('entity_type', 20);

            $table->unsignedSmallInteger('year');

            $table->unsignedInteger('last_number')
                ->default(0);

            $table->timestamps();

            $table->unique(
                ['entity_type', 'year'],
                'institutional_code_sequences_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutional_code_sequences');
    }
};
