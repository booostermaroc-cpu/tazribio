<?php

namespace App\Filament\Support;

use App\Models\Client;
use App\Models\Order;
use App\Support\WhatsAppUrl;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class OrderContactActions
{
    private const WHATSAPP_MESSAGE = <<<'MSG'
السلام عليكم، هاهي لاكموند راه bien emballé وداك الخير انشاءلله راه كيما بغيتيه وبأحسن حلة
كيما قلتليك غدا انشاءلله ولا بعدو كحد أقصى غايتاصل بيك الليفرور باش ايجيبها ليك ( وراه عندك الحق تقلبها قبل ماتخلص الليفرور ).
MSG;

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
        return trim(self::WHATSAPP_MESSAGE);
    }

    public static function newOrderMessage(?string $orderNumber, ?float $amount, ?string $clientName = null): string
    {
        return trim(self::WHATSAPP_MESSAGE);
    }
}
