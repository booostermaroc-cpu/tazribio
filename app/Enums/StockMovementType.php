<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum StockMovementType: string
{
    use HasColorAndLabel;

    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case Return = 'return';


    public function color(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'danger',
            self::Adjustment => 'warning',
            self::Return => 'info',
        };
    }
}
