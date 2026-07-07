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
        if ($model instanceof User && $model->is($user)) {
            return false;
        }

        if ($model instanceof User
            && $model->role === \App\Enums\UserRole::Admin
            && User::query()->where('role', \App\Enums\UserRole::Admin)->where('is_active', true)->count() <= 1) {
            return false;
        }

        return RolePermission::can($user, $this->resource(), 'delete');
    }
}
