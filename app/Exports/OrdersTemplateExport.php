<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'ORD-EXAMPLE-001',
                'Client Example',
                '0612345678',
                'Casablanca',
                '123 Rue Example',
                'new',
                'unpaid',
                'whatsapp',
                '100',
                '30',
                '0',
                '130',
                'Product SKU',
                '2',
                '50',
                'Order notes',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'order_number',
            'client_name',
            'client_phone',
            'city',
            'address',
            'status',
            'payment_status',
            'source',
            'total_amount',
            'delivery_fee',
            'discount',
            'final_amount',
            'product_sku',
            'quantity',
            'unit_price',
            'notes',
        ];
    }
}
