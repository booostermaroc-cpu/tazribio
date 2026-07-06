<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ReturnBonStatus;
use App\Exceptions\ReturnScanException;
use App\Models\Order;
use App\Models\ReturnBon;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReturnScanService
{
    public function __construct(
        protected OrderService $orderService,
        protected TrackingService $trackingService,
        protected NotificationService $notificationService,
    ) {}

    public function findOrderByCode(string $code): ?Order
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $order = Order::query()->where('order_number', $code)->first();

        if ($order) {
            return $order;
        }

        $returnBon = ReturnBon::query()->where('barcode_token', $code)->orWhere('return_number', $code)->first();

        return $returnBon?->order;
    }

    public function processScan(string $code, ?int $userId = null): ReturnBon
    {
        $order = $this->findOrderByCode($code);

        if (! $order) {
            throw ReturnScanException::orderNotFound();
        }

        if (! in_array($order->status, [OrderStatus::Shipped, OrderStatus::Delivered], true)) {
            throw ReturnScanException::invalidStatus($order->status?->label() ?? $order->status);
        }

        return DB::transaction(function () use ($order, $userId) {
            $returnBon = ReturnBon::query()->firstOrCreate(
                ['order_id' => $order->id],
                [
                    'return_number' => $this->generateReturnNumber(),
                    'barcode_token' => $order->order_number,
                    'reason' => __('codflow.returns.scan_default_reason'),
                    'status' => ReturnBonStatus::Received,
                ]
            );

            if ($order->status !== OrderStatus::Returned) {
                $order->update(['status' => OrderStatus::Returned]);
            }

            $this->trackingService->recordOrderStatusChange($order, OrderStatus::Returned, __('codflow.returns.scanned'));

            $this->notificationService->returnScanned($order, $returnBon);

            return $returnBon->fresh(['order']);
        });
    }

    public function generateReturnNumber(): string
    {
        $prefix = SettingService::get()->return_bon_prefix ?? 'RET';

        return $prefix.'-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
    }
}
