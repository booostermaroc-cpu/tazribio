<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Montre Connectée Pro',
                'sku' => 'SKU-WATCH-001',
                'purchase_price' => 150.00,
                'selling_price' => 299.00,
                'current_stock' => 50,
                'stock_alert' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'Écouteurs Bluetooth X2',
                'sku' => 'SKU-BT-002',
                'purchase_price' => 45.00,
                'selling_price' => 89.00,
                'current_stock' => 120,
                'stock_alert' => 15,
                'status' => 'active',
            ],
            [
                'name' => 'Power Bank 20000mAh',
                'sku' => 'SKU-PB-003',
                'purchase_price' => 60.00,
                'selling_price' => 119.00,
                'current_stock' => 8,
                'stock_alert' => 10,
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
