<?php

namespace App\Exports;

use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockMovementsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return StockMovement::query()
            ->with(['product', 'warehouse', 'user'])
            ->latest();
    }

    public function headings(): array
    {
        return [
            'product_sku',
            'product_name',
            'warehouse',
            'type',
            'quantity',
            'reason',
            'order_id',
            'user',
            'created_at',
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->product?->sku,
            $movement->product?->name,
            $movement->warehouse?->name,
            $movement->type?->value,
            $movement->quantity,
            $movement->reason,
            $movement->order_id,
            $movement->user?->name,
            $movement->created_at?->toDateTimeString(),
        ];
    }
}
