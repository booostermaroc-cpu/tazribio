<?php

namespace App\Filament\Resources\Shipments\Schemas;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\Labels;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Section::make(__('codflow.shipments.section_shipment'))
                            ->schema([
                                TextEntry::make('tracking_number')
                                    ->label(Labels::field('tracking_number'))
                                    ->copyable(),
                                TextEntry::make('delivery_status')
                                    ->label(Labels::field('delivery_status'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state instanceof ShipmentStatus ? $state->label() : ShipmentStatus::tryFrom((string) $state)?->label())
                                    ->color(fn ($state) => $state instanceof ShipmentStatus ? $state->color() : ShipmentStatus::tryFrom((string) $state)?->color()),
                                TextEntry::make('deliveryCompany.name')
                                    ->label(Labels::field('delivery_company'))
                                    ->placeholder('—'),
                                TextEntry::make('delivery_date')
                                    ->label(Labels::field('delivery_date'))
                                    ->date()
                                    ->placeholder('—'),
                            ]),
                        Section::make(__('codflow.shipments.section_order'))
                            ->schema([
                                TextEntry::make('order.order_number')
                                    ->label(Labels::field('order'))
                                    ->url(fn ($record): ?string => $record->order_id
                                        ? OrderResource::getUrl('view', ['record' => $record->order_id])
                                        : null)
                                    ->copyable(),
                                TextEntry::make('order.client.full_name')
                                    ->label(Labels::field('client'))
                                    ->placeholder('—'),
                                TextEntry::make('order.final_amount')
                                    ->label(Labels::field('final_amount'))
                                    ->money('MAD')
                                    ->placeholder('—'),
                                TextEntry::make('order.status')
                                    ->label(Labels::field('status'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state?->label())
                                    ->color(fn ($state) => $state?->color()),
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make(__('codflow.shipments.section_ameex'))
                    ->schema([
                        TextEntry::make('ameex_parcel_code')
                            ->label(__('codflow.ui.ameex_parcel_code'))
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('ameex_delivery_note_ref')
                            ->label(Labels::field('ameex_delivery_note_ref'))
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('ameex_last_status_name')
                            ->label(__('codflow.ui.ameex_last_status'))
                            ->placeholder('—'),
                        TextEntry::make('last_tracking_update')
                            ->label(Labels::field('last_tracking_update'))
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
