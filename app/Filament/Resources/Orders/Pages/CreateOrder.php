<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Exceptions\OrderValidationException;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\OrderContactActions;
use App\Models\Client;
use App\Services\OrderCalculationService;
use App\Services\OrderPaymentValidator;
use App\Support\WhatsAppUrl;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('contactWhatsApp')
                ->label(__('codflow.order.contact_whatsapp'))
                ->icon(\Filament\Support\Icons\Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(fn (): ?string => $this->whatsAppUrl())
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->whatsAppUrl() !== null),
        ];
    }

    protected function whatsAppUrl(): ?string
    {
        $clientId = $this->data['client_id'] ?? null;

        if (blank($clientId)) {
            return null;
        }

        $client = Client::query()->find($clientId);

        return WhatsAppUrl::url(
            $client?->phone,
            OrderContactActions::newOrderMessage(
                $this->data['order_number'] ?? null,
                isset($this->data['final_amount']) ? (float) $this->data['final_amount'] : null,
                $client?->full_name,
            ),
        );
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $raw = $this->form->getRawState();
        $items = $this->normalizeItems($raw['items'] ?? []);

        $this->validateOrderData(array_merge($raw, ['items' => $items]));

        $data = app(OrderCalculationService::class)->applyCalculatedAmounts(array_merge($data, [
            'items' => $items,
            'delivery_fee' => $raw['delivery_fee'] ?? $data['delivery_fee'] ?? 0,
            'discount' => $raw['discount'] ?? $data['discount'] ?? 0,
        ]));

        $data['created_by'] = auth()->id();

        return $data;
    }

    /** @return array<int, array<string, mixed>> */
    protected function normalizeItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values($items);
    }

    /** @param  array<string, mixed>  $data */
    protected function validateOrderData(array $data): void
    {
        $items = $data['items'] ?? [];

        if (! is_array($items) || count($items) < 1) {
            throw OrderValidationException::noItems();
        }

        foreach ($items as $item) {
            if ((float) ($item['quantity'] ?? 0) <= 0) {
                throw OrderValidationException::invalidItemQuantity();
            }

            if ((float) ($item['unit_price'] ?? 0) < 0) {
                throw OrderValidationException::invalidItemPrice();
            }
        }

        app(OrderPaymentValidator::class)->validate($data);
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (OrderValidationException $exception) {
            Notification::make()
                ->title(__('codflow.validation.form_error'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
