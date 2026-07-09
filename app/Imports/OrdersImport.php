<?php

namespace App\Imports;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Rules\MoroccanPhone;
use App\Services\OrderCalculationService;
use App\Services\SettingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrdersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $settings = SettingService::get();

        foreach ($rows as $row) {
            if (blank($row['client_phone'] ?? null) && blank($row['client_name'] ?? null)) {
                continue;
            }

            Validator::make($row->toArray(), [
                'client_name' => ['required', 'string', 'max:191'],
                'client_phone' => ['required', new MoroccanPhone],
                'quantity' => ['nullable', 'numeric', 'min:1'],
                'unit_price' => ['nullable', 'numeric', 'min:0'],
                'total_amount' => ['nullable', 'numeric', 'min:0'],
                'delivery_fee' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'final_amount' => ['nullable', 'numeric', 'min:0'],
            ])->validate();

            $client = Client::query()->firstOrCreate(
                ['phone' => preg_replace('/\s+/', '', (string) $row['client_phone'])],
                [
                    'full_name' => $row['client_name'],
                    'city' => $row['city'] ?? null,
                    'address' => $row['address'] ?? null,
                ]
            );

            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            $totalAmount = (float) ($row['total_amount'] ?? ($quantity * $unitPrice));
            $deliveryFee = (float) ($row['delivery_fee'] ?? $settings->default_delivery_fee);
            $discount = (float) ($row['discount'] ?? 0);
            $totals = app(OrderCalculationService::class)->calculateTotals(
                [['quantity' => $quantity, 'total_price' => $totalAmount]],
                $deliveryFee,
                $discount,
            );
            $totalAmount = $totals['total_amount'] > 0 ? $totals['total_amount'] : $totalAmount;
            $finalAmount = (float) ($row['final_amount'] ?? $totals['final_amount']);

            $order = Order::create([
                'order_number' => $row['order_number'] ?? $settings->order_prefix.'-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
                'client_id' => $client->id,
                'city' => $row['city'] ?? $client->city,
                'address' => $row['address'] ?? $client->address,
                'status' => OrderStatus::tryFrom((string) ($row['status'] ?? 'new')) ?? OrderStatus::New,
                'payment_status' => PaymentStatus::tryFrom((string) ($row['payment_status'] ?? 'unpaid')) ?? PaymentStatus::Unpaid,
                'source' => OrderSource::tryFrom((string) ($row['source'] ?? 'other')) ?? OrderSource::Other,
                'total_amount' => $totalAmount,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'notes' => $row['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            if ($sku = $row['product_sku'] ?? null) {
                $product = Product::query()->where('sku', $sku)->first();

                if ($product) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice > 0 ? $unitPrice : $product->selling_price,
                        'total_price' => $quantity * ($unitPrice > 0 ? $unitPrice : $product->selling_price),
                    ]);
                }
            }
        }
    }
}
