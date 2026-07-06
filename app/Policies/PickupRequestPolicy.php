<?php

namespace App\Policies;

class PickupRequestPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'pickup_requests';
    }
}
