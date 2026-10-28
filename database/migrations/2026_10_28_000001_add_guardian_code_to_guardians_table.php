<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            // Nullable para permitir migrar registros históricos sin alterarlos.
            $table->string('guardian_code', 8)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('guardians', fn (Blueprint $table) => $table->dropColumn('guardian_code'));
    }
};
