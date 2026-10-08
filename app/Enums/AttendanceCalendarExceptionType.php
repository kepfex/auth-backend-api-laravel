<?php

namespace App\Enums;

enum AttendanceCalendarExceptionType: string
{
    case NonWorking = 'non_working';
    case ScheduleOverride = 'schedule_override';

    public function label(): string
    {
        return match ($this) {
            self::NonWorking => 'Día no lectivo',
            self::ScheduleOverride => 'Horario excepcional',
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
