<?php

namespace App\Policies;

class WarehousePolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'warehouses';
    }
}
