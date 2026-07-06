<?php

namespace App\Policies;

class ShipmentPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'shipments';
    }
}
