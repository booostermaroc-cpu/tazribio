<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Filament\Support\DashboardMetrics;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    public function reserveForOrder(Order $order): void
    {
        if ($order->stock_deducted) {
            return;
        }

        $order->loadMissing('items.product');

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = $item->product;

                if ($product->current_stock < $item->quantity) {
                    throw new InsufficientStockException($product, $item->quantity);
                }
            }

            foreach ($order->items as $item) {
                $this->applyMovement(
                    product: $item->product,
                    type: StockMovementType::Out,
                    quantity: $item->quantity,
                    reason: "Reserved for order {$order->order_number}",
                    order: $order,
                );
            }

            $order->forceFill(['stock_deducted' => true])->save();
        });
    }

    public function restoreForOrder(Order $order, string $reason): void
    {
        if (! $order->stock_deducted) {
            return;
        }

        $order->loadMissing('items.product');

        DB::transaction(function () use ($order, $reason) {
            foreach ($order->items as $item) {
                $this->applyMovement(
                    product: $item->product,
                    type: StockMovementType::Return,
                    quantity: $item->quantity,
                    reason: $reason,
                    order: $order,
                );
            }

            $order->forceFill(['stock_deducted' => false])->save();
        });
    }

    public function applyMovement(
        Product $product,
        StockMovementType $type,
        int $quantity,
        string $reason,
        ?Order $order = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $warehouse = $this->defaultWarehouse();

        return DB::transaction(function () use ($product, $type, $quantity, $reason, $order, $warehouse) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $delta = match ($type) {
                StockMovementType::In, StockMovementType::Return => $quantity,
                StockMovementType::Out => -$quantity,
                StockMovementType::Adjustment => $quantity,
            };

            $newStock = $product->current_stock + $delta;

            if ($newStock < 0) {
                throw new InsufficientStockException($product, $quantity);
            }

            $product->update(['current_stock' => $newStock]);

            $this->syncWarehouseProduct($warehouse, $product, $delta);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => $type->value,
                'quantity' => $quantity,
                'reason' => $reason,
                'user_id' => Auth::id() ?? $order?->created_by ?? 1,
                'order_id' => $order?->id,
            ]);

            $this->checkLowStock($product->fresh());
            DashboardMetrics::clearCache();

            return $movement;
        });
    }

    public function checkLowStock(Product $product): void
    {
        if ($product->isLowStock()) {
            $this->notificationService->lowStock($product);
        }
    }

    protected function syncWarehouseProduct(Warehouse $warehouse, Product $product, int $delta): void
    {
        $warehouseProduct = WarehouseProduct::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
            ],
            ['quantity' => 0],
        );

        $warehouseProduct->update([
            'quantity' => max(0, $warehouseProduct->quantity + $delta),
        ]);
    }

    protected function defaultWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()->where('is_active', true)->orderBy('id')->first();

        if (! $warehouse) {
            throw new \RuntimeException('No active warehouse configured for stock operations.');
        }

        return $warehouse;
    }
}
