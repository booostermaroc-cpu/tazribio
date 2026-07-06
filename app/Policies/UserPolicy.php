<?php

namespace App\Policies;

use App\Models\User;
use App\Support\RolePermission;

class UserPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'users';
    }

    public function delete(User $user, mixed $model): bool
    {
        return RolePermission::can($user, $this->resource(), 'delete');
    }
}
