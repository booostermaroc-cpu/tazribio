<?php

namespace App\Services;

use App\Enums\CarrierFeeTrigger;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Str;

class CarrierFeeService
{
    /** @return list<array{key: string, label: string, amount: float, trigger: string}> */
    public function rules(?Setting $settings = null): array
    {
        $settings ??= SettingService::get();
        $raw = $settings->carrier_fee_rules;

        if (! is_array($raw) || $raw === []) {
            return self::defaultRules();
        }

        return self::normalizeRules($raw);
    }

    /** @return list<array{key: string, label: string, amount: float, trigger: string}> */
    public static function defaultRules(): array
    {
        return [
            [
                'key' => 'delivery',
                'label' => 'Livraison',
                'amount' => 30,
                'trigger' => CarrierFeeTrigger::Delivered->value,
            ],
            [
                'key' => 'return',
                'label' => 'Retour',
                'amount' => 10,
                'trigger' => CarrierFeeTrigger::Returned->value,
            ],
            [
                'key' => 'return_packaged',
                'label' => 'Retour avec emballage',
                'amount' => 14,
                'trigger' => CarrierFeeTrigger::ReturnedPackaged->value,
            ],
        ];
    }

    /** @param  array<int, array<string, mixed>>  $rules */
    public static function normalizeRules(array $rules): array
    {
        $normalized = [];

        foreach ($rules as $index => $rule) {
            if (! is_array($rule) || blank($rule['label'] ?? null)) {
                continue;
            }

            $label = (string) $rule['label'];
            $amount = max(0, (float) ($rule['amount'] ?? 0));
            $trigger = (string) ($rule['trigger'] ?? CarrierFeeTrigger::Delivered->value);

            $normalized[] = [
                'key' => filled($rule['key'] ?? null) ? (string) $rule['key'] : Str::slug($label),
                'label' => $label,
                'amount' => $amount,
                'trigger' => $trigger,
            ];
        }

        return $normalized;
    }

    /** @return array{key: string, label: string, amount: float, trigger: string}|null */
    public function resolveRuleForOrder(Order $order): ?array
    {
        $trigger = $this->resolveTriggerForOrder($order);

        if ($trigger === null) {
            return null;
        }

        foreach ($this->rules() as $rule) {
            if ($rule['trigger'] === $trigger->value) {
                return $rule;
            }
        }

        return null;
    }

    public function resolveTriggerForOrder(Order $order): ?CarrierFeeTrigger
    {
        return match ($order->status) {
            OrderStatus::Delivered => CarrierFeeTrigger::Delivered,
            OrderStatus::Returned => $order->returnBons()->where('with_packaging', true)->exists()
                ? CarrierFeeTrigger::ReturnedPackaged
                : CarrierFeeTrigger::Returned,
            OrderStatus::Shipped => CarrierFeeTrigger::Shipped,
            default => null,
        };
    }

    public function syncOrderFee(Order $order): Order
    {
        $rule = $this->resolveRuleForOrder($order);

        $order->update([
            'carrier_fee_amount' => $rule['amount'] ?? 0,
            'carrier_fee_rule_key' => $rule['key'] ?? null,
        ]);

        return $order->fresh();
    }

    public function syncAllOrders(): int
    {
        $count = 0;

        Order::query()
            ->whereIn('status', [
                OrderStatus::Delivered->value,
                OrderStatus::Returned->value,
                OrderStatus::Shipped->value,
            ])
            ->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    $this->syncOrderFee($order);
                    $count++;
                }
            });

        return $count;
    }

    public function totalPayable(): float
    {
        return (float) Order::query()
            ->whereIn('status', [OrderStatus::Delivered, OrderStatus::Returned])
            ->sum('carrier_fee_amount');
    }

    public function monthPayable(): float
    {
        return (float) Order::query()
            ->whereIn('status', [OrderStatus::Delivered, OrderStatus::Returned])
            ->where('updated_at', '>=', now()->startOfMonth())
            ->sum('carrier_fee_amount');
    }

    public function projectedShippedPayable(): float
    {
        $deliveryAmount = collect($this->rules())
            ->firstWhere('trigger', CarrierFeeTrigger::Delivered->value)['amount'] ?? 0;

        if ($deliveryAmount <= 0) {
            return (float) Order::query()
                ->where('status', OrderStatus::Shipped)
                ->sum('carrier_fee_amount');
        }

        return (float) Order::query()
            ->where('status', OrderStatus::Shipped)
            ->count() * (float) $deliveryAmount;
    }

    /** @return array<int, array{label: string, count: int, amount: float}> */
    public function breakdown(): array
    {
        $rules = $this->rules();
        $rows = [];

        foreach ($rules as $rule) {
            $query = Order::query()->where('carrier_fee_rule_key', $rule['key']);

            if (in_array($rule['trigger'], [CarrierFeeTrigger::Delivered->value, CarrierFeeTrigger::Returned->value, CarrierFeeTrigger::ReturnedPackaged->value], true)) {
                $query->whereIn('status', [OrderStatus::Delivered, OrderStatus::Returned]);
            } elseif ($rule['trigger'] === CarrierFeeTrigger::Shipped->value) {
                $query->where('status', OrderStatus::Shipped);
            }

            $count = (int) $query->count();
            $amount = (float) $query->sum('carrier_fee_amount');

            $rows[] = [
                'label' => $rule['label'],
                'count' => $count,
                'amount' => $amount,
            ];
        }

        return $rows;
    }

    public function ruleLabel(?string $key): ?string
    {
        if (blank($key)) {
            return null;
        }

        foreach ($this->rules() as $rule) {
            if ($rule['key'] === $key) {
                return $rule['label'];
            }
        }

        return $key;
    }
}
