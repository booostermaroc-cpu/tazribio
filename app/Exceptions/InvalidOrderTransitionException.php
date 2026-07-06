<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use Exception;

class InvalidOrderTransitionException extends Exception
{
    public static function make(OrderStatus $from, OrderStatus $to): self
    {
        return new self(__('codflow.validation.invalid_transition', [
            'from' => $from->label(),
            'to' => $to->label(),
        ]));
    }
}
