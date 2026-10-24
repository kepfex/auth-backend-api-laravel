
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->string('code', 50)
                ->nullable()
                ->unique()
                ->after('id');
        });

        $codes = [
            'Principal' => 'DIRECTOR',
            'Auxiliar de Educación' => 'AUXILIAR_EDUCACION',
            'Profesor' => 'DOCENTE',
            'Administrativo' => 'ADMINISTRATIVO',
        ];

        foreach ($codes as $name => $code) {
            DB::table('positions')
                ->where('name', $name)
                ->update(['code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique('positions_code_unique');
            $table->dropColumn('code');
        });
    }
};
