<?php

namespace App\Enums;

enum AttendanceMarkStatus: string
{
    case OnTime = 'on_time';
    case Late = 'late';
    case Early = 'early';
    case Unmatched = 'unmatched';

    public function label(): string
    {
        return match ($this) {
            self::OnTime => 'A tiempo',
            self::Late => 'Tarde',
            self::Early => 'Anticipado',
            self::Unmatched => 'Sin coincidencia',
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
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
