<?php

namespace App\Policies;

use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    abstract protected function resource(): string;

    public function viewAny(User $user): bool
    {
        return RolePermission::can($user, $this->resource(), 'viewAny');
    }

    public function view(User $user, mixed $model): bool
    {
        return RolePermission::can($user, $this->resource(), 'view');
    }

    public function create(User $user): bool
    {
        return RolePermission::can($user, $this->resource(), 'create');
    }

    public function update(User $user, mixed $model): bool
    {
        return RolePermission::can($user, $this->resource(), 'update');
    }

    public function delete(User $user, mixed $model): bool
    {
        return RolePermission::can($user, $this->resource(), 'delete');
    }

    public function restore(User $user, mixed $model): bool
    {
        return RolePermission::can($user, $this->resource(), 'delete');
    }

    public function forceDelete(User $user, mixed $model): bool
    {
        return RolePermission::can($user, $this->resource(), 'delete');
    }
}
