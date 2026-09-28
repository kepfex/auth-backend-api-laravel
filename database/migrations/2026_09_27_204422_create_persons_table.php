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
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->string('codument_type', 12);
            $table->string('codument_number', 20);
            $table->string('first_names', 100);
            $table->string('paternal_surname', 100);
            $table->string('maternal_surmane', 100);
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address', 255)->nullable();
            $table->char('sex', 1)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['codument_type', 'document_number']);
            $table->index(['paternal_surname', 'maternal_surname', 'first_names']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
