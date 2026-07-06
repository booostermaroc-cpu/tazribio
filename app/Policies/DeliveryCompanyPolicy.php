<?php

namespace App\Policies;

class DeliveryCompanyPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'delivery_companies';
    }
}
