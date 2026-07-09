<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum PaymentStatus: string
{
    use HasColorAndLabel;

    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Refunded = 'refunded';


    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Paid => 'success',
            self::Refunded => 'danger',
        };
    }
}
