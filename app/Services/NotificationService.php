<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\ComplaintCreatedNotification;
use App\Notifications\LowStockNotification;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderReturnedNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Models\Complaint;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    /** @param  UserRole|array<UserRole>  $roles */
    public function notifyRoles(UserRole|array $roles, Notification $notification): void
    {
        $roles = is_array($roles) ? $roles : [$roles];

        $this->getUsersByRoles($roles)->each(
            fn (User $user) => $user->notify($notification)
        );
    }

    public function notifyAdminsAndManagers(Notification $notification): void
    {
        $this->notifyRoles([UserRole::Admin, UserRole::Manager], $notification);
    }

    public function newOrder(Order $order): void
    {
        $this->notifyAdminsAndManagers(new NewOrderNotification($order));
    }

    public function lowStock(Product $product): void
    {
        $this->notifyRoles(
            [UserRole::Admin, UserRole::Manager, UserRole::StockManager],
            new LowStockNotification($product)
        );
    }

    public function complaintCreated(Complaint $complaint): void
    {
        $this->notifyAdminsAndManagers(new ComplaintCreatedNotification($complaint));
    }

    public function orderDelivered(Order $order): void
    {
        $this->notifyAdminsAndManagers(new OrderDeliveredNotification($order));
    }

    public function orderReturned(Order $order): void
    {
        $this->notifyAdminsAndManagers(new OrderReturnedNotification($order));
    }

    public function paymentReceived(Invoice $invoice): void
    {
        $this->notifyRoles(
            [UserRole::Admin, UserRole::Manager, UserRole::Finance],
            new PaymentReceivedNotification($invoice)
        );
    }

    public function orderConfirmed(Order $order): void
    {
        $this->notifyAdminsAndManagers(new \App\Notifications\OrderConfirmedNotification($order));
    }

    public function orderSentToDelivery(Order $order, \App\Models\DeliveryCompany $company): void
    {
        $this->notifyAdminsAndManagers(new \App\Notifications\OrderSentToDeliveryNotification($order, $company));
    }

    public function deliveryApiError(?Order $order, string $message): void
    {
        $this->notifyAdminsAndManagers(new \App\Notifications\DeliveryApiErrorNotification($order, $message));
    }

    public function returnScanned(Order $order, \App\Models\ReturnBon $returnBon): void
    {
        $this->notifyAdminsAndManagers(new \App\Notifications\ReturnScannedNotification($order, $returnBon));
    }

    public function paymentPending(Order $order): void
    {
        $this->notifyRoles(
            [UserRole::Admin, UserRole::Manager, UserRole::Finance],
            new \App\Notifications\PaymentPendingNotification($order)
        );
    }

    /** @param  array<UserRole>  $roles */
    public function getUsersByRoles(array $roles): Collection
    {
        $roleValues = array_map(fn (UserRole $role) => $role->value, $roles);

        return User::query()
            ->where('is_active', true)
            ->whereIn('role', $roleValues)
            ->get();
    }
}
