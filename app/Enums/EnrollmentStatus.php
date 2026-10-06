<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Enrolled = 'matriculado'; // Alumno actualmente matriculado.
    case Completed = 'culminado'; // Terminó ese período académico.
    case Withdrawn = 'retirado'; //Dejó de estudiar durante el período.
    case Transferred = 'trasladado'; // Salió de la institución por traslado.

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'Matriculado',
            self::Completed => 'Culminado',
            self::Withdrawn => 'Retirado',
            self::Transferred => 'Trasladado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
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
