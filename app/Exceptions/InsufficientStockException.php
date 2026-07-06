<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly int $requestedQuantity,
    ) {
        parent::__construct(
            __('codflow.validation.insufficient_stock', ['product' => $product->name ?? $product->sku])
            .' '
            .__('codflow.validation.insufficient_stock_detail', [
                'available' => $product->current_stock,
                'requested' => $requestedQuantity,
            ])
        );
    }
}
