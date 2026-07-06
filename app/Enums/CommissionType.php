<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum CommissionType: string
{
    use HasColorAndLabel;

    case None = 'none';
    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Fixed => 'info',
            self::Percentage => 'success',
        };
    }
}
