<?php

namespace App\Policies;

class ComplaintPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'complaints';
    }
}
