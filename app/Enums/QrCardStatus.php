<?php

namespace App\Enums;

enum QrCardStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Revoked => 'Revocada',
            self::Expired => 'Expirada',
        };
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
