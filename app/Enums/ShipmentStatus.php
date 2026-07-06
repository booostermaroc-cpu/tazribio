<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum ShipmentStatus: string
{
    use HasColorAndLabel;

    case Pending = 'pending';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Failed = 'failed';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::PickedUp => 'info',
            self::InTransit => 'primary',
            self::Delivered => 'success',
            self::Returned => 'warning',
            self::Failed => 'danger',
        };
    }
}
