<?php

namespace App\Enums;

enum AttendanceDayStatus: string
{
    case Pending = 'pending';
    case Present = 'present';
    case Partial = 'partial';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Present => 'Presente',
            self::Partial => 'Asistencia parcial',
            self::Absent => 'Ausente',
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
