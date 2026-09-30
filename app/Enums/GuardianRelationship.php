<?php

namespace App\Enums;

enum GuardianRelationship: string
{
    case Father = 'padre';
    case Mother = 'madre';
    case Grandfather = 'abuelo';
    case Grandmother = 'abuela';
    case Uncle = 'tío';
    case Aunt = 'tía';
    case Sibling = 'hermano/a';
    case LegalGuardian = 'tutor_legal';
    case Other = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Father => 'Padre',
            self::Mother => 'Madre',
            self::Grandfather => 'Abuelo',
            self::Grandmother => 'Abuela',
            self::Uncle => 'Tío',
            self::Aunt => 'Tía',
            self::Sibling => 'Hermano/a',
            self::LegalGuardian => 'Tutor legal',
            self::Other => 'Otro',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(
            fn(self $relationship) => [
                'value' => $relationship->value,
                'label' => $relationship->label(),
            ],
            self::cases()
        );
    }
}
