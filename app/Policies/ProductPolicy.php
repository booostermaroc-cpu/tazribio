<?php

namespace App\Policies;

class ProductPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'products';
    }
}
