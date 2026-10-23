<?php

namespace App\Enums;

enum InstitutionalCodeType: string
{
    case Student = 'student';
    case Employee = 'employee';
    case Guardian = 'guardian';

    /**
     * Prefijo utilizado en el código institucional.
     */
    public function prefix(): string
    {
        return match ($this) {
            self::Student => 'A',
            self::Employee => 'E',
            self::Guardian => 'P',
        };
    }
}
