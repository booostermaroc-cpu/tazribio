<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Builder;

class CarrierStuckOrders
{
    public static function thresholdDays(): int
    {
        $days = (int) SettingService::get()->carrier_stuck_days;

        return max(1, $days ?: 60);
    }

    /** @return Builder<Order> */
    public static function query(): Builder
    {
        $cutoff = now()->subDays(self::thresholdDays())->endOfDay();

        return Order::query()
            ->where('status', OrderStatus::Shipped)
            ->whereRaw(
                'COALESCE(
                    (SELECT MIN(created_at) FROM order_tracking_histories
                     WHERE order_id = orders.id AND status = ?),
                    orders.updated_at
                ) <= ?',
                [OrderStatus::Shipped->value, $cutoff]
            );
    }

    public static function count(): int
    {
        return static::query()->count();
    }

    public static function totalAmount(): float
    {
        return (float) static::query()->sum('final_amount');
    }

    public static function applyTo(Builder $query): Builder
    {
        return $query->whereIn('orders.id', static::query()->select('orders.id'));
    }
}
