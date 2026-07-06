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

        if ($type === CommissionType::None) {
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

    public function createCommission(Order $order, User $agent, CommissionType $type, float $value): ?AgentCommission
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

        $this->notificationService->notifyAdminsAndManagers(new AgentCommissionCreatedNotification($commission));

        return $commission;
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
