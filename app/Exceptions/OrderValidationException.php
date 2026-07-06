<?php

namespace App\Exceptions;

use Exception;

class OrderValidationException extends Exception
{
    public static function noItems(): self
    {
        return new self(__('codflow.validation.order_no_items'));
    }

    public static function noShipment(): self
    {
        return new self(__('codflow.validation.order_no_shipment'));
    }

    public static function invalidItemQuantity(): self
    {
        return new self(__('codflow.validation.item_quantity_invalid'));
    }

    public static function invalidItemPrice(): self
    {
        return new self(__('codflow.validation.item_price_invalid'));
    }

    public static function paymentReferenceRequired(\App\Enums\PaymentMethod $method): self
    {
        return new self(__('codflow.validation.payment_reference_required', [
            'method' => $method->label(),
        ]));
    }

    public static function insufficientStock(string $productName): self
    {
        return new self(__('codflow.validation.insufficient_stock', ['product' => $productName]));
    }
}
