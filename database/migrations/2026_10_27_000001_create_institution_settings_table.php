<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institution_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('abbreviation', 20);
            $table->string('modular_code', 20)->nullable();
            $table->string('local_code', 20)->nullable();
            $table->string('ugel', 100)->nullable();
            $table->string('dre', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_settings');
    }
};
