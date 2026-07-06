<?php

namespace App\Policies;

class PaymentPlanningPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'payment_plannings';
    }
}
