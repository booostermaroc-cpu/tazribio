<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum ComplaintStatus: string
{
    use HasColorAndLabel;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Rejected = 'rejected';


    public function color(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::InProgress => 'warning',
            self::Resolved => 'success',
            self::Rejected => 'gray',
        };
    }
}
