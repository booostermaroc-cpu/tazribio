<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderValidationException;
use App\Models\Order;
use App\Models\OrderTrackingHistory;
use App\Support\OrderWorkflow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected StockService $stockService,
        protected NotificationService $notificationService,
        protected TrackingService $trackingService,
        protected CommissionService $commissionService,
    ) {}

    public function validateStatusTransition(Order $order, OrderStatus $to): void
    {
        $from = $this->resolveStatus($order->getOriginal('status'));

        if (! OrderWorkflow::canTransition($from, $to)) {
            throw InvalidOrderTransitionException::make($from, $to);
        }

        $this->validateBusinessRules($order, $from, $to);
    }

    public function validateBusinessRules(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        if ($from !== $to && $from === OrderStatus::New && ! in_array($to, [
            OrderStatus::Cancelled,
            OrderStatus::NoAnswer,
            OrderStatus::Busy,
            OrderStatus::Voicemail,
            OrderStatus::WrongNumber,
            OrderStatus::SmsSent,
        ], true)) {
            $this->validateHasItems($order);
        }

        if ($to === OrderStatus::Confirmed || $to === OrderStatus::Prepared || $to === OrderStatus::Shipped) {
            $this->validateHasItems($order);
        }

        if ($to === OrderStatus::Shipped) {
            $this->validateHasShipment($order);
        }
    }

    public function validateHasItems(Order $order): void
    {
        if ($order->items()->count() === 0) {
            throw OrderValidationException::noItems();
        }
    }

    public function validateHasShipment(Order $order): void
    {
        if (! $order->shipments()->exists()) {
            throw OrderValidationException::noShipment();
        }
    }

    public function transitionTo(Order $order, OrderStatus $status): Order
    {
        $payload = ['status' => $status];

        if ($status === OrderStatus::Confirmed && ! $order->confirmed_by) {
            $payload['confirmed_by'] = auth()->id();
        }

        $order->update($payload);

        return $order->fresh();
    }

    public function transitionTowards(Order $order, OrderStatus $target): Order
    {
        $order = $order->fresh();
        $guard = 0;

        while ($order->status !== $target && $guard++ < 6) {
            if (OrderWorkflow::canTransition($order->status, $target)) {
                return $this->transitionTo($order, $target);
            }

            $next = $this->nextStatusTowards($order->status, $target);

            if ($next === null) {
                break;
            }

            $this->transitionTo($order, $next);
            $order = $order->fresh();
        }

        return $order;
    }

    protected function nextStatusTowards(OrderStatus $from, OrderStatus $target): ?OrderStatus
    {
        $allowed = OrderWorkflow::allowedTransitions()[$from->value] ?? [];

        if ($allowed === []) {
            return null;
        }

        $ranks = [
            OrderStatus::New->value => 0,
            OrderStatus::NoAnswer->value => 0,
            OrderStatus::Busy->value => 0,
            OrderStatus::Voicemail->value => 0,
            OrderStatus::WrongNumber->value => 0,
            OrderStatus::SmsSent->value => 0,
            OrderStatus::Confirmed->value => 1,
            OrderStatus::Prepared->value => 2,
            OrderStatus::Shipped->value => 3,
            OrderStatus::Delivered->value => 4,
            OrderStatus::Returned->value => 4,
            OrderStatus::Cancelled->value => 99,
        ];

        $targetRank = $ranks[$target->value] ?? 99;

        $best = null;
        $bestRank = -1;

        foreach ($allowed as $candidate) {
            $rank = $ranks[$candidate->value] ?? 99;

            if ($rank <= $targetRank && $rank > $bestRank) {
                $best = $candidate;
                $bestRank = $rank;
            }
        }

        return $best;
    }

    public function afterStatusChanged(Order $order, OrderStatus $previousStatus): void
    {
        $newStatus = $order->status;

        if ($previousStatus === $newStatus) {
            return;
        }

        DB::transaction(function () use ($order, $previousStatus, $newStatus) {
            if (OrderWorkflow::shouldDeductStock($newStatus) && ! $order->stock_deducted) {
                $this->stockService->reserveForOrder($order);
            }

            if (OrderWorkflow::shouldRestoreStock($newStatus) && $order->stock_deducted) {
                $this->stockService->restoreForOrder(
                    $order,
                    "Stock restored after order {$order->order_number} status changed to {$newStatus->value}"
                );
            }

            $this->trackingService->recordOrderStatusChange($order, $newStatus);

            $this->commissionService->handleOrderStatusChange($order, $newStatus);

            app(CarrierFeeService::class)->syncOrderFee($order->fresh());

            match ($newStatus) {
                OrderStatus::Confirmed => $this->notificationService->orderConfirmed($order),
                OrderStatus::Delivered => $this->notificationService->orderDelivered($order),
                OrderStatus::Returned => $this->notificationService->orderReturned($order),
                default => null,
            };
        });
    }

    public function markPaymentReceived(Order $order): Order
    {
        if ($order->status !== OrderStatus::Delivered) {
            throw new \RuntimeException('Only delivered orders can be marked as paid.');
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            return $order;
        }

        $order->update(['payment_status' => PaymentStatus::Paid]);

        if ($invoice = $order->invoice) {
            app(PaymentService::class)->markInvoicePaid($invoice);
        }

        return $order->fresh();
    }

    protected function resolveStatus(OrderStatus|string|null $status): OrderStatus
    {
        if ($status instanceof OrderStatus) {
            return $status;
        }

        return OrderStatus::from((string) $status);
    }
}
