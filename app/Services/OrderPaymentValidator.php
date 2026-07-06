<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\OrderValidationException;

class OrderPaymentValidator
{
    public function validate(array $data): void
    {
        $method = PaymentMethod::tryFrom((string) ($data['payment_method'] ?? '')) ?? PaymentMethod::Cod;

        if ($method->requiresPaymentDetails()) {
            if (blank($data['payment_reference'] ?? null)) {
                throw OrderValidationException::paymentReferenceRequired($method);
            }
        }
    }
}
