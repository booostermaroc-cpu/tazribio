<?php

namespace App\Services;

use App\Enums\CommissionApplyOn;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AgentCommission;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AgentCommissionCreatedNotification;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    public function handleOrderStatusChange(Order $order, OrderStatus $newStatus): void
    {
        if (! in_array($newStatus, [OrderStatus::Confirmed, OrderStatus::Delivered], true)) {
            return;
        }

        $agent = $this->resolveAgent($order);

        if (! $agent) {
            return;
        }

        [$type, $value, $applyOn] = $this->resolveCommissionRules($agent);

        if ($type === CommissionType::None || $value <= 0) {
            return;
        }

        $trigger = $newStatus === OrderStatus::Confirmed
            ? CommissionApplyOn::Confirmed
            : CommissionApplyOn::Delivered;

        if ($applyOn !== $trigger) {
            return;
        }

        $this->createCommission($order, $agent, $type, $value);
    }

    public function createCommission(Order $order, User $agent, CommissionType $type, float $value, bool $notify = true): ?AgentCommission
    {
        if (AgentCommission::query()->where('order_id', $order->id)->where('user_id', $agent->id)->exists()) {
            return null;
        }

        $amount = match ($type) {
            CommissionType::Fixed => round($value, 2),
            CommissionType::Percentage => round(((float) $order->final_amount) * ($value / 100), 2),
            default => 0.0,
        };

        if ($amount <= 0) {
            return null;
        }

        $commission = AgentCommission::query()->create([
            'user_id' => $agent->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'status' => CommissionStatus::Pending,
            'calculated_at' => now(),
        ]);

        if ($notify) {
            $this->notificationService->notifyAdminsAndManagers(new AgentCommissionCreatedNotification($commission));
        }

        return $commission;
    }

    public function unpaidTotalForUser(User $user): float
    {
        return (float) AgentCommission::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [CommissionStatus::Pending, CommissionStatus::Approved])
            ->sum('amount');
    }

    public function paidTotalForUser(User $user): float
    {
        return (float) AgentCommission::query()
            ->where('user_id', $user->id)
            ->where('status', CommissionStatus::Paid)
            ->sum('amount');
    }

    public function confirmedOrdersCount(User $user): int
    {
        return (int) Order::query()
            ->where('confirmed_by', $user->id)
            ->count();
    }

    public function markAllUnpaidAsPaid(User $user): int
    {
        return AgentCommission::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [CommissionStatus::Pending, CommissionStatus::Approved])
            ->update([
                'status' => CommissionStatus::Paid,
                'paid_at' => now(),
            ]);
    }

    public function syncCommissionsForUser(User $user): int
    {
        $orders = Order::query()
            ->where('confirmed_by', $user->id)
            ->whereIn('status', [
                OrderStatus::Confirmed->value,
                OrderStatus::Prepared->value,
                OrderStatus::Shipped->value,
                OrderStatus::Delivered->value,
            ])
            ->get();

        $created = 0;

        foreach ($orders as $order) {
            if (AgentCommission::query()->where('order_id', $order->id)->where('user_id', $user->id)->exists()) {
                continue;
            }

            [$type, $value, $applyOn] = $this->resolveCommissionRules($user);

            if ($type === CommissionType::None || $value <= 0) {
                continue;
            }

            if ($applyOn === CommissionApplyOn::Delivered && $order->status !== OrderStatus::Delivered) {
                continue;
            }

            if ($this->createCommission($order, $user, $type, $value, notify: false) !== null) {
                $created++;
            }
        }

        return $created;
    }

    /** @return array{0: CommissionType, 1: float, 2: CommissionApplyOn} */
    protected function resolveCommissionRules(User $agent): array
    {
        $type = $agent->confirmation_commission_type ?? CommissionType::None;
        $applyOn = $agent->apply_commission_on ?? CommissionApplyOn::Delivered;

        if ($type === CommissionType::None) {
            $settings = SettingService::get();
            $type = CommissionType::tryFrom((string) $settings->agent_commission_default_type) ?? CommissionType::None;
            $applyOn = CommissionApplyOn::tryFrom((string) $settings->agent_commission_apply_on) ?? CommissionApplyOn::Delivered;
            $value = (float) $settings->agent_commission_default_value;
        } else {
            $value = (float) $agent->confirmation_commission_value;
        }

        return [$type, $value, $applyOn];
    }

    protected function resolveAgent(Order $order): ?User
    {
        if ($order->confirmed_by) {
            return User::query()->find($order->confirmed_by);
        }

        if ($order->created_by) {
            return User::query()->find($order->created_by);
        }

        return null;
    }
}
