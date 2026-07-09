<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum PaymentPlanningStatus: string
{
    use HasColorAndLabel;

    case Planned = 'planned';
    case Received = 'received';
    case Delayed = 'delayed';


    public function color(): string
    {
        return match ($this) {
            self::Planned => 'info',
            self::Received => 'success',
            self::Delayed => 'danger',
        };
    }
}
