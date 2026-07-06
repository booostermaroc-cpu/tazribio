<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum ReturnBonStatus: string
{
    use HasColorAndLabel;

    case Requested = 'requested';
    case Accepted = 'accepted';
    case Received = 'received';
    case Refused = 'refused';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Accepted => 'info',
            self::Received => 'success',
            self::Refused => 'danger',
        };
    }
}
