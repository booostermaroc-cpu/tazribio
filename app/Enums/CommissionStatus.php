<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum CommissionStatus: string
{
    use HasColorAndLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }
}
