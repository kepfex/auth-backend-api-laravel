<?php

namespace App\Enums;

enum AttendanceMarkSource: string
{
    case Manual = 'manual';
    case Qr = 'qr';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Qr => 'Código QR',
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
            fn (self $source) => [
                'value' => $source->value,
                'label' => $source->label(),
            ],
            self::cases()
        );
    }
}