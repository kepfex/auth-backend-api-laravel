<?php

namespace App\Services;

use App\Enums\InstitutionalCodeType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstitutionalCodeService
{
    private const MAX_SEQUENCE = 99999;

    /**
     * Generar el siguiente código institucional.
     *
     * Debe utilizarse dentro de la misma transacción
     * que registra la entidad correspondiente.
     */
    public function generate(
        InstitutionalCodeType $type,
        ?int $year = null
    ): string {
        $year ??= (int) now()->year;

        if ($year < 2000 || $year > 2099) {
            throw new RuntimeException(
                'El año está fuera del rango soportado '
                . 'por el formato institucional.'
            );
        }

        return DB::transaction(function () use (
            $type,
            $year
        ) {
            $sequenceKey = [
                'entity_type' => $type->value,
                'year' => $year,
            ];

            // Inicializar el contador si todavía no existe.
            // La restricción UNIQUE evita duplicar la fila.
            DB::table('institutional_code_sequences')
                ->insertOrIgnore([
                    ...$sequenceKey,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            // Bloquear la secuencia durante su actualización.
            $sequence = DB::table(
                'institutional_code_sequences'
            )
                ->where($sequenceKey)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException(
                    'No se pudo obtener la secuencia institucional.'
                );
            }

            $nextNumber = $sequence->last_number + 1;

            if ($nextNumber > self::MAX_SEQUENCE) {
                throw new RuntimeException(
                    'Se agotaron los correlativos '
                    . 'disponibles para este tipo y año.'
                );
            }

            DB::table('institutional_code_sequences')
                ->where('id', $sequence->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            $prefix = $type->prefix();

            $shortYear = substr(
                (string) $year,
                -2
            );

            $correlative = str_pad(
                (string) $nextNumber,
                5,
                '0',
                STR_PAD_LEFT
            );

            return $prefix
                . $shortYear
                . $correlative;
        }, 3);
    }
}
