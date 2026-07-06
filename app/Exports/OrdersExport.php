<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Order::query()
            ->with(['client', 'shipment.deliveryCompany'])
            ->latest();
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
            'tracking_number',
            'delivery_company',
            'notes',
            'created_at',
        ];
    }

    public function map($order): array
    {
        return [
            $order->order_number,
            $order->client?->full_name,
            $order->client?->phone,
            $order->city,
            $order->address,
            $order->status?->value,
            $order->payment_status?->value,
            $order->source?->value,
            $order->total_amount,
            $order->delivery_fee,
            $order->discount,
            $order->final_amount,
            $order->shipment?->tracking_number,
            $order->shipment?->deliveryCompany?->name,
            $order->notes,
            $order->created_at?->toDateTimeString(),
        ];
    }
}
