<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum ProductStatus: string
{
    use HasColorAndLabel;

    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'gray',
        };
    }
}
