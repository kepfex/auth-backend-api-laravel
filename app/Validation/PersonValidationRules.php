<?php

namespace App\Validation;

use Illuminate\Validation\Rule;

final class PersonValidationRules
{
    /**
     * Reglas para los datos de una persona.
     *
     * $prefix:
     *   ''        → document_type
     *   'person.' → person.document_type
     *
     * $partial:
     *   false → creación
     *   true  → actualización parcial
     */
    public static function rules(
        string $prefix = '',
        bool $partial = false
    ): array {
        $required = $partial
            ? ['sometimes', 'required']
            : ['required'];

        $optional = $partial
            ? ['sometimes', 'nullable']
            : ['nullable'];

        return [
            $prefix . 'document_type' => [
                ...$required,
                Rule::in(['DNI', 'CE', 'PASSPORT']),
            ],

            $prefix . 'document_number' => [
                ...$required,
                'string',
                'max:20',
            ],

            $prefix . 'first_names' => [
                ...$required,
                'string',
                'max:100',
            ],

            $prefix . 'paternal_surname' => [
                ...$required,
                'string',
                'max:100',
            ],

            $prefix . 'maternal_surname' => [
                ...$optional,
                'string',
                'max:100',
            ],

            $prefix . 'phone' => [
                ...$optional,
                'string',
                'max:20',
            ],

            $prefix . 'email' => [
                ...$optional,
                'email',
                'max:150',
            ],

            $prefix . 'birth_date' => [
                ...$optional,
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            $prefix . 'address' => [
                ...$optional,
                'string',
                'max:255',
            ],

            $prefix . 'sex' => [
                ...$optional,
                Rule::in(['M', 'F']),
            ],

            $prefix . 'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
