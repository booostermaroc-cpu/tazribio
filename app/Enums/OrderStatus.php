<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum OrderStatus: string
{
    use HasColorAndLabel;

    case New = 'new';
    case NoAnswer = 'no_answer';
    case Busy = 'busy';
    case Voicemail = 'voicemail';
    case WrongNumber = 'wrong_number';
    case SmsSent = 'sms_sent';
    case Confirmed = 'confirmed';
    case Prepared = 'prepared';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::NoAnswer, self::Busy, self::Voicemail => 'warning',
            self::WrongNumber => 'danger',
            self::SmsSent => 'gray',
            self::Confirmed => 'info',
            self::Prepared => 'warning',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Returned => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
