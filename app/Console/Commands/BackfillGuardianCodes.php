<?php

namespace App\Console\Commands;

use App\Enums\InstitutionalCodeType;
use App\Models\Guardian;
use App\Services\InstitutionalCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillGuardianCodes extends Command
{
    protected $signature = 'institution:backfill-guardian-codes {--year= : Año de emisión (por defecto, año actual)}';
    protected $description = 'Asigna códigos a apoderados históricos sin modificar los códigos existentes';

    public function handle(InstitutionalCodeService $codes): int
    {
        $year = $this->option('year') === null ? (int) now()->year : (int) $this->option('year');
        $updated = 0;
        Guardian::withTrashed()->whereNull('guardian_code')->orderBy('id')->chunkById(100, function ($guardians) use ($codes, $year, &$updated) {
            foreach ($guardians as $guardian) {
                DB::transaction(function () use ($codes, $year, $guardian, &$updated) {
                    $locked = Guardian::withTrashed()->lockForUpdate()->findOrFail($guardian->id);
                    if ($locked->guardian_code !== null) return;
                    $locked->guardian_code = $codes->generate(InstitutionalCodeType::Guardian, $year);
                    $locked->save();
                    $updated++;
                });
            }
        });
        $this->info("Códigos de apoderados asignados: {$updated}");
        return self::SUCCESS;
    }
}
