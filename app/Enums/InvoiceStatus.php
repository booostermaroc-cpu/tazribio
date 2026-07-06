<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum InvoiceStatus: string
{
    use HasColorAndLabel;

    case Pending = 'pending';
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
            self::Paid => 'success',
            self::Cancelled => 'gray',
        };
    }
}
