<?php

namespace App\Enums;

enum QrScanResult: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Duplicate = 'duplicate';
    case Invalid = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Aceptado',
            self::Rejected => 'Rechazado',
            self::Duplicate => 'Duplicado',
            self::Invalid => 'Inválido',
        };
    }

    public static function values(): array
    {
        return array_column(
            self::cases(),
            'value'
        );
    }

    public static function options(): array
    {
        return array_map(
            fn(self $result) => [
                'value' => $result->value,
                'label' => $result->label(),
            ],
            self::cases()
        );
    }
}
