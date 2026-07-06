<?php

namespace App\Policies;

class OrderPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'orders';
    }
}
