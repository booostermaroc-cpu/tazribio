<?php

namespace App\Services;

use App\Enums\OrderConfirmationAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderConfirmationLog;
use Illuminate\Support\Facades\Auth;

class ConfirmationTrackingService
{
    public function log(Order $order, OrderConfirmationAction $action, ?string $notes = null): OrderConfirmationLog
    {
        return OrderConfirmationLog::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'notes' => $notes,
        ]);
    }

    public function logWithStatusNote(Order $order, OrderConfirmationAction $action, ?OrderStatus $resultStatus = null): OrderConfirmationLog
    {
        $notes = $resultStatus !== null
            ? __('codflow.confirmation_tracking.new_status', ['status' => $resultStatus->label()])
            : null;

        return $this->log($order, $action, $notes);
    }

    public function hasAction(Order $order, OrderConfirmationAction $action): bool
    {
        return OrderConfirmationLog::query()
            ->where('order_id', $order->id)
            ->where('action', $action)
            ->exists();
    }

    /** @return list<OrderConfirmationAction> */
    public function missingProcessSteps(Order $order): array
    {
        $missing = [];

        foreach ([OrderConfirmationAction::WhatsappContact, OrderConfirmationAction::PhoneCall] as $step) {
            if (! $this->hasAction($order, $step) && ! $this->hasConfirmedContact($order)) {
                $missing[] = $step;
            }
        }

        return $missing;
    }

    public function hasConfirmedContact(Order $order): bool
    {
        return $this->hasAction($order, OrderConfirmationAction::ConfirmedViaWhatsapp)
            || $this->hasAction($order, OrderConfirmationAction::ConfirmedViaCall)
            || $this->hasAction($order, OrderConfirmationAction::OrderConfirmed);
    }

    public function processComplete(Order $order): bool
    {
        if (! $this->hasConfirmedContact($order)) {
            return false;
        }

        return $this->hasAction($order, OrderConfirmationAction::WhatsappContact)
            || $this->hasAction($order, OrderConfirmationAction::PhoneCall)
            || $this->hasAction($order, OrderConfirmationAction::ConfirmedViaWhatsapp)
            || $this->hasAction($order, OrderConfirmationAction::ConfirmedViaCall);
    }
}
