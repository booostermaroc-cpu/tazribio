<?php

namespace App\Filament\Resources\ReturnBons\Pages;

use App\Filament\Resources\ReturnBons\ReturnBonResource;
use App\Models\Order;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnBon extends CreateRecord
{
    protected static string $resource = ReturnBonResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['barcode_token'] ?? null) && filled($data['order_id'] ?? null)) {
            $order = Order::query()->find($data['order_id']);
            $data['barcode_token'] = $order?->order_number;
        }

        return $data;
    }
}
