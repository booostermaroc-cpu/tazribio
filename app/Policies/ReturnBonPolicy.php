<?php

namespace App\Policies;

class ReturnBonPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'return_bons';
    }
}
