<?php

namespace App\Policies;

class StockMovementPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'stock_movements';
    }
}
