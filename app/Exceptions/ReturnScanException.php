<?php

namespace App\Exceptions;

use Exception;

class ReturnScanException extends Exception
{
    public static function orderNotFound(): self
    {
        return new self(__('codflow.returns.order_not_found'));
    }

    public static function invalidStatus(string $status): self
    {
        return new self(__('codflow.returns.invalid_status', ['status' => $status]));
    }
}
