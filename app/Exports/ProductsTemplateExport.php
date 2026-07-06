<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'Sample Product',
                'SKU-001',
                '40',
                '79.99',
                '100',
                '10',
                'active',
            ],
        ];
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
}
