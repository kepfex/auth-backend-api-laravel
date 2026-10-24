<?php

namespace App\Validation;

use App\Models\Person;
use Illuminate\Validation\Validator;

final class PersonDocumentValidator
{
    public static function normalize(mixed $number): string
    {
        return strtoupper(trim((string) $number));
    }

    public static function validate(
        Validator $validator,
        ?string $type,
        ?string $number,
        string $field = 'document_number',
        ?int $ignorePersonId = null
    ): void {
        // No consultar la BD si ya existen
        // errores básicos de validación.
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        if ($type === null || $number === null) {
            return;
        }

        // Validación específica del DNI peruano.
        if (
            $type === 'DNI' &&
            !preg_match('/^[0-9]{8}$/', $number)
        ) {
            $validator->errors()->add(
                $field,
                'El DNI debe contener ocho dígitos.'
            );

            return;
        }

        // Incluye personas eliminadas lógicamente.
        $query = Person::withTrashed()
            ->where('document_type', $type)
            ->where('document_number', $number);

        // Durante una actualización, ignorar
        // solamente a la persona actual.
        if ($ignorePersonId !== null) {
            $query->whereKeyNot($ignorePersonId);
        }

        if ($query->exists()) {
            $validator->errors()->add(
                $field,
                'El documento ya existe. '
                    . 'Busca y selecciona la persona registrada.'
            );
        }
    }
}
