<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum ComplaintPriority: string
{
    use HasColorAndLabel;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'warning',
            self::High => 'danger',
        };
    }
}
