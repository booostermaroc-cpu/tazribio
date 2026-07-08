<?php

namespace App\Filament\Resources\Shipments\Tables;

use App\Enums\DeliveryProvider;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Support\AmeexNotifications;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use App\Models\Shipment;
use App\Services\DeliveryIntegrationService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tracking_number')
                    ->searchable()
                    ->copyable()
                    ->url(fn (Shipment $record): string => ShipmentResource::getUrl('view', ['record' => $record])),
                TextColumn::make('order.order_number')
                    ->searchable()
                    ->label(Labels::field('order'))
                    ->url(fn (Shipment $record): ?string => $record->order_id
                        ? OrderResource::getUrl('view', ['record' => $record->order_id])
                        : null),
                TextColumn::make('deliveryCompany.name')->label(Labels::field('carrier')),
                EnumColumn::badge('delivery_status', ShipmentStatus::class),
                TextColumn::make('ameex_last_status_name')->label(__('codflow.ui.ameex_last_status'))->toggleable(),
                TextColumn::make('delivery_date')->date(),
            ])
            ->filters([
                SelectFilter::make('delivery_status')->options(ShipmentStatus::options()),
                SelectFilter::make('delivery_company_id')->relationship('deliveryCompany', 'name')->searchable()->preload()->label(Labels::field('carrier')),
            ])
            ->recordActions([
                Action::make('sendOrderToAmeex')
                    ->label(__('codflow.delivery.send_order_ameex'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(__('codflow.delivery.ameex_stock_confirm'))
                    ->visible(fn (Shipment $record): bool => $record->deliveryCompany?->provider === DeliveryProvider::Ameex
                        && $record->order !== null)
                    ->action(function (Shipment $record): void {
                        AmeexNotifications::notify(app(DeliveryIntegrationService::class)->sendShipmentOrderToAmeex($record));
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('ameexMassTracking')
                        ->label(__('codflow.delivery.ameex_mass_tracking'))
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $result = app(DeliveryIntegrationService::class)->refreshMassTracking($records);
                            AmeexNotifications::notify($result);
                        }),
                    BulkAction::make('ameexMassInfo')
                        ->label(__('codflow.delivery.ameex_mass_info'))
                        ->icon('heroicon-o-information-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $result = app(DeliveryIntegrationService::class)->fetchMassInfo($records);
                            AmeexNotifications::notify($result);
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
