<?php

namespace App\Policies;

class OrderReviewPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'order_reviews';
    }
}
