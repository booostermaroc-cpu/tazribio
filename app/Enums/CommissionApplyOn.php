<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum CommissionApplyOn: string
{
    use HasColorAndLabel;

    case Confirmed = 'confirmed';
    case Delivered = 'delivered';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'info',
            self::Delivered => 'success',
        };
    }
}
