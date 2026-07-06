<?php

namespace App\Support;

use App\Enums\OrderStatus;

class OrderWorkflow
{
    /** @return list<OrderStatus> */
    public static function confirmationPhaseStatuses(): array
    {
        return [
            OrderStatus::New,
            OrderStatus::NoAnswer,
            OrderStatus::Busy,
            OrderStatus::Voicemail,
            OrderStatus::WrongNumber,
            OrderStatus::SmsSent,
        ];
    }

    /** @return list<OrderStatus> */
    public static function confirmationTargetStatuses(): array
    {
        return [
            OrderStatus::Confirmed,
            OrderStatus::Cancelled,
            OrderStatus::NoAnswer,
            OrderStatus::Busy,
            OrderStatus::Voicemail,
            OrderStatus::WrongNumber,
            OrderStatus::SmsSent,
        ];
    }

    /** @return array<string, list<OrderStatus>> */
    public static function allowedTransitions(): array
    {
        $confirmationTargets = self::confirmationTargetStatuses();

        $transitions = [
            OrderStatus::Confirmed->value => [OrderStatus::Prepared, OrderStatus::Cancelled],
            OrderStatus::Prepared->value => [OrderStatus::Shipped],
            OrderStatus::Shipped->value => [OrderStatus::Delivered, OrderStatus::Returned],
            OrderStatus::Delivered->value => [],
            OrderStatus::Returned->value => [],
            OrderStatus::Cancelled->value => [],
        ];

        foreach (self::confirmationPhaseStatuses() as $from) {
            $transitions[$from->value] = $confirmationTargets;
        }

        return $transitions;
    }

    public static function canTransition(OrderStatus $from, OrderStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::allowedTransitions()[$from->value] ?? [], true);
    }

    public static function isConfirmationPhase(OrderStatus $status): bool
    {
        return in_array($status, self::confirmationPhaseStatuses(), true);
    }

    public static function stockDeductionStatuses(): array
    {
        return [
            OrderStatus::Confirmed,
            OrderStatus::Prepared,
            OrderStatus::Shipped,
        ];
    }

    public static function stockRestoreStatuses(): array
    {
        return [
            OrderStatus::Cancelled,
            OrderStatus::Returned,
        ];
    }

    public static function shouldDeductStock(OrderStatus $status): bool
    {
        return in_array($status, self::stockDeductionStatuses(), true);
    }

    public static function shouldRestoreStock(OrderStatus $status): bool
    {
        return in_array($status, self::stockRestoreStatuses(), true);
    }
}
