<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum CarrierFeeTrigger: string
{
    use HasColorAndLabel;

    case Delivered = 'delivered';
    case Returned = 'returned';
    case ReturnedPackaged = 'returned_packaged';
    case Shipped = 'shipped';

}
