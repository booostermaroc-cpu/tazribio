<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum PickupRequestStatus: string
{
    use HasColorAndLabel;

    case Pending = 'pending';
    case Accepted = 'accepted';
    case Done = 'done';
    case Cancelled = 'cancelled';


    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'info',
            self::Done => 'success',
            self::Cancelled => 'gray',
        };
    }
}
