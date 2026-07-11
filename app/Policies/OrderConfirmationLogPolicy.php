<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\RolePermission;

class OrderConfirmationLogPolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'confirmation_tracking';
    }

    public function viewAny(User $user): bool
    {
        if (! in_array($user->role, [UserRole::Admin, UserRole::Manager], true)) {
            return false;
        }

        return RolePermission::canAccessResource($user, 'confirmation_tracking');
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, mixed $model): bool
    {
        return false;
    }

    public function delete(User $user, mixed $model): bool
    {
        return $user->role === UserRole::Admin;
    }
}
