<?php

namespace App\Policies;

class MessagePolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'messages';
    }
}
