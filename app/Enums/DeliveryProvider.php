<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum DeliveryProvider: string
{
    use HasColorAndLabel;

    case Manual = 'manual';
    case Ameex = 'ameex';
    case Other = 'other';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Ameex => 'primary',
            self::Other => 'info',
        };
    }
}
