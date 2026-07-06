<?php

namespace App\Policies;

class ClientPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'clients';
    }
}
