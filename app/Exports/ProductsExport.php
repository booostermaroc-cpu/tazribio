<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Product::query()->latest();
    }

    public function headings(): array
    {
        return [
            'name',
            'sku',
            'purchase_price',
            'selling_price',
            'current_stock',
            'stock_alert',
            'status',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->purchase_price,
            $product->selling_price,
            $product->current_stock,
            $product->stock_alert,
            $product->status?->value,
        ];
    }
}
