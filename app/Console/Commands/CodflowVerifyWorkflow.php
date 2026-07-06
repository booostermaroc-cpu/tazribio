<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Complaint;
use App\Models\Invoice;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

class CodflowVerifyWorkflow extends Command
{
    protected $signature = 'codflow:verify-workflow';

    protected $description = 'Verify CODFlow stock workflow, notifications, and activity logging';

    public function handle(): int
    {
        $this->info('Running CODFlow workflow verification...');

        NotificationFacade::fake();

        $admin = User::query()->where('email', 'admin@codflow.test')->first();
        if (! $admin) {
            $this->error('Admin user not found. Run php artisan db:seed first.');

            return self::FAILURE;
        }

        auth()->login($admin);

        $product = Product::query()->first();
        if (! $product) {
            $this->error('No products found. Run seeders first.');

            return self::FAILURE;
        }

        $initialStock = $product->current_stock;
        $client = Client::query()->firstOrCreate(
            ['phone' => '0600999999'],
            ['full_name' => 'Verify Client', 'city' => 'Casablanca']
        );

        $results = [];

        DB::beginTransaction();

        try {
            $order = $this->createOrder($client, $product, $admin, 2);

            $results['activity_log_on_create'] = Activity::query()
                ->where('description', 'like', "%{$order->order_number}%")
                ->exists();

            $results['new_order_notification'] = $this->notificationWasSent($admin, \App\Notifications\NewOrderNotification::class);

            $order->update(['status' => OrderStatus::Confirmed]);
            $product->refresh();

            $results['stock_decreases_on_confirm'] = $product->current_stock === ($initialStock - 2);
            $results['stock_movement_created'] = StockMovement::query()->where('order_id', $order->id)->exists();
            $results['order_stock_deducted_flag'] = (bool) $order->fresh()->stock_deducted;

            $order->update(['status' => OrderStatus::Cancelled]);
            $product->refresh();

            $results['stock_restores_on_cancel'] = $product->current_stock === $initialStock;
            $results['stock_deducted_cleared_on_cancel'] = ! $order->fresh()->stock_deducted;

            $oversellOrder = $this->createOrder($client, $product, $admin, $initialStock + 100);
            $blocked = false;

            try {
                $oversellOrder->update(['status' => OrderStatus::Confirmed]);
            } catch (InsufficientStockException) {
                $blocked = true;
            }

            $results['negative_stock_blocked'] = $blocked;

            $invalidBlocked = false;

            try {
                $order->update(['status' => OrderStatus::Delivered]);
            } catch (InvalidOrderTransitionException) {
                $invalidBlocked = true;
            }

            $results['invalid_transition_blocked'] = $invalidBlocked;

            Complaint::create([
                'order_id' => $order->id,
                'client_id' => $client->id,
                'subject' => 'Verify complaint',
                'description' => 'Test complaint body',
                'status' => 'open',
                'priority' => 'medium',
            ]);

            $results['complaint_notification'] = $this->notificationWasSent($admin, \App\Notifications\ComplaintCreatedNotification::class);

            $deliveredOrder = $this->createOrder($client, $product, $admin, 1);
            $this->advanceTo($deliveredOrder, OrderStatus::Delivered);

            $results['delivered_notification'] = $this->notificationWasSent($admin, \App\Notifications\OrderDeliveredNotification::class);

            $invoice = Invoice::create([
                'invoice_number' => 'INV-TEST-'.Str::upper(Str::random(4)),
                'order_id' => $deliveredOrder->id,
                'amount' => 50,
                'status' => InvoiceStatus::Pending,
            ]);

            app(PaymentService::class)->markInvoicePaid($invoice);
            $results['payment_notification'] = $this->notificationWasSent($admin, \App\Notifications\PaymentReceivedNotification::class);
            $results['order_marked_paid'] = $deliveredOrder->fresh()->payment_status === PaymentStatus::Paid;

            $returnOrder = $this->createOrder($client, $product, $admin, 1);
            $this->advanceTo($returnOrder, OrderStatus::Shipped);
            $returnOrder->update(['status' => OrderStatus::Returned]);

            $results['returned_notification'] = $this->notificationWasSent($admin, \App\Notifications\OrderReturnedNotification::class);

            $product->update(['current_stock' => 1, 'stock_alert' => 5]);
            app(StockService::class)->checkLowStock($product);
            $results['low_stock_notification'] = $this->notificationWasSent($admin, \App\Notifications\LowStockNotification::class);

            DB::rollBack();
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->error('Verification failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Check', 'Result'],
            collect($results)->map(fn ($pass, $check) => [
                'check' => $check,
                'result' => $pass ? 'PASS' : 'FAIL',
            ])->values()->all()
        );

        if (collect($results)->contains(fn ($pass) => ! $pass)) {
            $this->error('Some checks failed.');

            return self::FAILURE;
        }

        $this->info('All CODFlow workflow checks passed.');

        return self::SUCCESS;
    }

    protected function notificationWasSent(User $user, string $notificationClass): bool
    {
        return NotificationFacade::sent($user, $notificationClass)->isNotEmpty();
    }

    protected function createOrder(Client $client, Product $product, User $admin, int $quantity): Order
    {
        $order = Order::create([
            'order_number' => 'TEST-'.Str::upper(Str::random(6)),
            'client_id' => $client->id,
            'total_amount' => $quantity * 50,
            'delivery_fee' => 0,
            'discount' => 0,
            'final_amount' => $quantity * 50,
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Unpaid,
            'source' => 'other',
            'created_by' => $admin->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => 50,
            'total_price' => $quantity * 50,
        ]);

        return $order->fresh(['items']);
    }

    protected function advanceTo(Order $order, OrderStatus $target): void
    {
        $path = match ($target) {
            OrderStatus::Delivered => [
                OrderStatus::Confirmed,
                OrderStatus::Prepared,
                OrderStatus::Shipped,
                OrderStatus::Delivered,
            ],
            OrderStatus::Shipped => [
                OrderStatus::Confirmed,
                OrderStatus::Prepared,
                OrderStatus::Shipped,
            ],
            default => [$target],
        };

        foreach ($path as $status) {
            if ($status === OrderStatus::Shipped) {
                $this->ensureShipment($order);
            }

            $order->update(['status' => $status]);
            $order->refresh();
        }
    }

    protected function ensureShipment(Order $order): void
    {
        if ($order->shipments()->exists()) {
            return;
        }

        Shipment::create([
            'order_id' => $order->id,
            'delivery_company_id' => DeliveryCompany::query()->value('id'),
            'tracking_number' => 'TEST-TRK-'.Str::upper(Str::random(6)),
            'status' => 'pending',
        ]);
    }
}
