<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

class RolePermission
{
    public static function can(User $user, string $resource, string $action): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if (! self::canAccessResource($user, $resource)) {
            return false;
        }

        $role = $user->role;

        if ($role === UserRole::Manager) {
            if ($resource === 'settings' && $action === 'delete') {
                return false;
            }

            if ($resource === 'users' && $action === 'delete') {
                return false;
            }

            return true;
        }

        if ($role === UserRole::Agent) {
            return in_array($resource, ['orders', 'clients', 'complaints', 'return_bons', 'order_reviews'], true)
                && in_array($action, ['viewAny', 'view', 'create', 'update'], true);
        }

        if ($role === UserRole::StockManager) {
            return in_array($resource, ['products', 'warehouses', 'stock_movements'], true)
                && $action !== 'delete';
        }

        if ($role === UserRole::DeliveryAgent) {
            return in_array($resource, ['shipments', 'delivery_companies', 'pickup_requests', 'orders'], true)
                && in_array($action, ['viewAny', 'view', 'create', 'update'], true);
        }

        if ($role === UserRole::Finance) {
            return in_array($resource, ['invoices', 'payment_plannings', 'expenses', 'orders'], true)
                && in_array($action, ['viewAny', 'view', 'create', 'update'], true);
        }

        return false;
    }

    public static function canAccessResource(User $user, string $resource): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $allowed = $user->allowed_resources;

        if (! is_array($allowed) || $allowed === []) {
            return in_array($resource, self::defaultResourcesForRole($user->role), true);
        }

        return in_array($resource, $allowed, true);
    }

    /** @return list<string> */
    public static function defaultResourcesForRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin => AppResource::values(),
            UserRole::Manager => AppResource::values(),
            UserRole::Agent => ['orders', 'clients', 'complaints', 'return_bons', 'order_reviews'],
            UserRole::StockManager => ['products', 'warehouses', 'stock_movements'],
            UserRole::DeliveryAgent => ['shipments', 'delivery_companies', 'pickup_requests', 'orders'],
            UserRole::Finance => ['invoices', 'payment_plannings', 'expenses', 'orders'],
        };
    }
}
