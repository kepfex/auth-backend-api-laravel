<?php

namespace App\Enums;

enum AttendanceScheduleType: string
{
    case Regular = 'regular';
    case Override = 'override';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Override => 'Excepcional',
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
            fn(self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}
