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

    public function mount(): void
    {
        parent::mount();

        $state = $this->form->getRawState();
        $this->form->fill(array_merge($state, [
            'delivery_fee' => 15,
        ]));
    }

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
        $raw = $this->form->getRawState();
        $clientId = $raw['client_id'] ?? null;
        $client = filled($clientId) ? Client::query()->find($clientId) : null;
        $phone = $client?->phone ?? ($raw['client_phone'] ?? null);
        $clientName = $client?->full_name ?? ($raw['client_full_name'] ?? null);
        $items = is_array($raw['items'] ?? null) ? $raw['items'] : [];
        $totals = app(OrderCalculationService::class)->calculateTotals(
            $items,
            (float) ($raw['delivery_fee'] ?? 15),
            (float) ($raw['discount'] ?? 0),
        );

        return WhatsAppUrl::url(
            $phone,
            OrderContactActions::newOrderMessage(
                $raw['order_number'] ?? null,
                $totals['carrier_cod_amount'],
                $clientName,
            ),
        );
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $raw = $this->form->getRawState();
        $items = $this->normalizeItems($raw['items'] ?? []);

        $this->validateOrderData(array_merge($raw, ['items' => $items]));

        $data['client_id'] = $this->resolveClientId($raw);

        $data = app(OrderCalculationService::class)->applyCalculatedAmounts(array_merge($data, [
            'items' => $items,
            'delivery_fee' => $raw['delivery_fee'] ?? $data['delivery_fee'] ?? 0,
            'discount' => $raw['discount'] ?? $data['discount'] ?? 0,
        ]));

        $data['created_by'] = auth()->id();

        return $data;
    }

    /** @param  array<string, mixed>  $raw */
    protected function resolveClientId(array $raw): int
    {
        if (filled($raw['client_id'] ?? null)) {
            $client = Client::query()->findOrFail($raw['client_id']);

            $client->update(array_filter([
                'full_name' => $raw['client_full_name'] ?? null,
                'second_phone' => $raw['client_second_phone'] ?? null,
                'city' => $raw['city'] ?? null,
                'address' => $raw['address'] ?? null,
            ], fn ($value) => filled($value)));

            return $client->id;
        }

        $phone = preg_replace('/\s+/', '', (string) ($raw['client_phone'] ?? ''));

        if (blank($raw['client_full_name'] ?? null) || blank($phone)) {
            throw OrderValidationException::missingClient();
        }

        $client = Client::query()->firstOrCreate(
            ['phone' => $phone],
            [
                'full_name' => $raw['client_full_name'],
                'second_phone' => $raw['client_second_phone'] ?? null,
                'city' => $raw['city'] ?? null,
                'address' => $raw['address'] ?? null,
            ],
        );

        if (! $client->wasRecentlyCreated) {
            $client->update(array_filter([
                'full_name' => $raw['client_full_name'],
                'second_phone' => $raw['client_second_phone'] ?? null,
                'city' => $raw['city'] ?? null,
                'address' => $raw['address'] ?? null,
            ], fn ($value) => filled($value)));
        }

        return $client->id;
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
