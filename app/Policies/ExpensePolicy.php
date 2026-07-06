<?php

namespace App\Policies;

class ExpensePolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'expenses';
    }
}
