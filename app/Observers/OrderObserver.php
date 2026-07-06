<?php

namespace App\Observers;

use App\Filament\Support\DashboardMetrics;
use App\Services\FinancialMetrics;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Activity;
use App\Models\Order;
use App\Services\NotificationService;
use App\Services\OrderProfitService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;

class OrderObserver
{
    public function __construct(
        protected OrderService $orderService,
        protected NotificationService $notificationService,
        protected OrderProfitService $orderProfitService,
    ) {}

    public function created(Order $order): void
    {
        $this->orderProfitService->syncAutoProfit($order->fresh());
        DashboardMetrics::clearCache();
        FinancialMetrics::clearCache();
        $this->log('created', "Order {$order->order_number} was created.");
        $this->notificationService->newOrder($order);
    }

    public function updating(Order $order): void
    {
        if ($order->isDirty('status')) {
            $this->orderService->validateStatusTransition(
                $order,
                $order->status instanceof OrderStatus ? $order->status : OrderStatus::from($order->status)
            );
        }
    }

    public function updated(Order $order): void
    {
        if ($order->payment_method === PaymentMethod::Cod || ! $order->profit_is_manual) {
            $this->orderProfitService->syncAutoProfit($order->fresh());
        }

        DashboardMetrics::clearCache();
        FinancialMetrics::clearCache();

        if ($order->wasChanged('status')) {
            $previous = $order->getOriginal('status');
            $previousStatus = $previous instanceof \App\Enums\OrderStatus
                ? $previous
                : \App\Enums\OrderStatus::from($previous);
            $status = $order->status?->label() ?? $order->status;
            $this->log('status_changed', "Order {$order->order_number} status changed to {$status}.");
            $this->orderService->afterStatusChanged($order, $previousStatus);
        } elseif ($order->wasChanged()) {
            $this->log('updated', "Order {$order->order_number} was updated.");
        }
    }

    public function deleted(Order $order): void
    {
        DashboardMetrics::clearCache();
        FinancialMetrics::clearCache();
        $this->log('deleted', "Order {$order->order_number} was deleted.");
    }

    protected function log(string $action, string $description): void
    {
        Activity::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
}
