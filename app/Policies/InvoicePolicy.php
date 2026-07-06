<?php

namespace App\Policies;

class InvoicePolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'invoices';
    }
}
