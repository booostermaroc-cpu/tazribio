<?php

namespace App\Filament\Support;

use App\Models\Client;
use App\Models\Order;
use App\Support\WhatsAppUrl;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class OrderContactActions
{
    public static function whatsAppAction(?string $phone, ?string $message = null): Action
    {
        return Action::make('contactWhatsApp')
            ->label(__('codflow.order.contact_whatsapp'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->url(fn (): ?string => WhatsAppUrl::url($phone, $message))
            ->openUrlInNewTab()
            ->visible(fn (): bool => WhatsAppUrl::url($phone, $message) !== null);
    }

    public static function whatsAppForClientId(?int $clientId, ?string $message = null): Action
    {
        $phone = $clientId ? Client::query()->find($clientId)?->phone : null;

        return self::whatsAppAction($phone, $message);
    }

    public static function whatsAppForOrder(Order $order): Action
    {
        return self::whatsAppAction(
            $order->client?->phone,
            self::orderMessage($order),
        );
    }

    public static function orderMessage(Order $order): string
    {
        $name = $order->client?->full_name ?? __('codflow.order.default_client_name');

        return __('codflow.order.whatsapp_message', [
            'name' => $name,
            'order' => $order->order_number,
            'amount' => number_format((float) $order->final_amount, 2, ',', ' '),
        ]);
    }

    public static function newOrderMessage(?string $orderNumber, ?float $amount, ?string $clientName = null): string
    {
        return __('codflow.order.whatsapp_message', [
            'name' => $clientName ?? __('codflow.order.default_client_name'),
            'order' => $orderNumber ?? '—',
            'amount' => number_format((float) ($amount ?? 0), 2, ',', ' '),
        ]);
    }
}
