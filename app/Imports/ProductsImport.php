<?php

namespace App\Imports;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (blank($row['sku'] ?? null) && blank($row['name'] ?? null)) {
                continue;
            }

            Validator::make($row->toArray(), [
                'name' => ['required', 'string', 'max:191'],
                'sku' => ['required', 'string', 'max:191'],
                'purchase_price' => ['nullable', 'numeric', 'min:0'],
                'selling_price' => ['nullable', 'numeric', 'min:0'],
                'current_stock' => ['nullable', 'integer', 'min:0'],
                'stock_alert' => ['nullable', 'integer', 'min:0'],
            ])->validate();

            Product::query()->updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'purchase_price' => (float) ($row['purchase_price'] ?? 0),
                    'selling_price' => (float) ($row['selling_price'] ?? 0),
                    'current_stock' => (int) ($row['current_stock'] ?? 0),
                    'stock_alert' => (int) ($row['stock_alert'] ?? 5),
                    'status' => ProductStatus::tryFrom((string) ($row['status'] ?? 'active')) ?? ProductStatus::Active,
                ]
            );
        }
    }
}
