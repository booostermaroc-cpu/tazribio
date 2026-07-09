<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\PaymentMethod;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Support\Labels;
use App\Filament\Support\OrderConfirmationSection;
use App\Filament\Support\OrderFulfillmentSection;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                OrderConfirmationSection::make(),
                OrderFulfillmentSection::make(),
                Grid::make(3)
                    ->schema([
                        Section::make(__('codflow.order.section_order'))
                            ->schema([
                                TextEntry::make('order_number')->copyable()->weight('bold'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state?->label())
                                    ->color(fn ($state) => $state?->color()),
                                TextEntry::make('payment_status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state?->label())
                                    ->color(fn ($state) => $state?->color()),
                                TextEntry::make('source')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state?->label()),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('creator.name')->label(Labels::field('created_by')),
                            ])
                            ->columnSpan(1),
                        Section::make(__('codflow.order.section_client'))
                            ->schema([
                                ImageEntry::make('client.logo_url')
                                    ->label(Labels::field('logo'))
                                    ->circular()
                                    ->visible(fn ($record) => filled($record->client?->logo_url)),
                                TextEntry::make('client.full_name')->label(Labels::field('client')),
                                TextEntry::make('client.phone')->label(Labels::field('phone'))->copyable(),
                                TextEntry::make('city')->label(Labels::field('city')),
                                TextEntry::make('address')->label(Labels::field('address'))->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                        Section::make(__('codflow.order.section_amounts'))
                            ->schema([
                                TextEntry::make('total_amount')->money('MAD'),
                                TextEntry::make('delivery_fee')
                                    ->label(Labels::field('delivery_fee'))
                                    ->money('MAD'),
                                TextEntry::make('carrier_cod_amount')
                                    ->label(__('codflow.order.carrier_cod_amount'))
                                    ->state(fn ($record) => $record->carrierCodAmount())
                                    ->money('MAD')
                                    ->helperText(__('codflow.order.carrier_cod_amount_hint')),
                                TextEntry::make('carrier_fee_amount')
                                    ->label(__('codflow.fields.carrier_fee_amount'))
                                    ->money('MAD')
                                    ->placeholder('—'),
                                TextEntry::make('carrier_fee_rule_key')
                                    ->label(__('codflow.fields.carrier_fee_rule'))
                                    ->formatStateUsing(fn ($state) => $state
                                        ? app(\App\Services\CarrierFeeService::class)->ruleLabel($state)
                                        : '—'),
                                TextEntry::make('discount')->money('MAD'),
                                TextEntry::make('final_amount')->money('MAD')->weight('bold'),
                                TextEntry::make('profit_amount')
                                    ->label(__('codflow.order.profit_amount'))
                                    ->money('MAD')
                                    ->placeholder('—')
                                    ->formatStateUsing(fn ($state, $record) => $record->isCod()
                                        ? null
                                        : $state)
                                    ->hint(fn ($record) => $record->isCod()
                                        ? __('codflow.order.profit_cod_excluded_hint')
                                        : ($record->profit_is_manual
                                            ? __('codflow.order.profit_manual_active_hint')
                                            : __('codflow.order.profit_auto_hint'))),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
                Section::make(__('codflow.shipments.linked_shipment'))
                    ->schema([
                        TextEntry::make('shipment.tracking_number')
                            ->label(Labels::field('tracking_number'))
                            ->placeholder('—')
                            ->copyable()
                            ->url(fn ($record): ?string => $record->shipment
                                ? ShipmentResource::getUrl('view', ['record' => $record->shipment])
                                : null),
                        TextEntry::make('shipment.deliveryCompany.name')
                            ->label(Labels::field('delivery_company'))
                            ->placeholder('—'),
                        TextEntry::make('shipment.delivery_status')
                            ->label(Labels::field('delivery_status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->color(fn ($state) => $state?->color())
                            ->placeholder('—'),
                        TextEntry::make('shipment.ameex_parcel_code')
                            ->label(__('codflow.ui.ameex_parcel_code'))
                            ->copyable()
                            ->placeholder('—'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Section::make(Labels::section('payment'))
                    ->schema([
                        TextEntry::make('payment_method')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label()),
                        TextEntry::make('payment_status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->color(fn ($state) => $state?->color()),
                        TextEntry::make('payment_reference')->placeholder('—'),
                        TextEntry::make('payment_receiver_name')->placeholder('—'),
                        TextEntry::make('payment_receiver_rib')->placeholder('—'),
                        TextEntry::make('payment_received_at')->dateTime()->placeholder('—'),
                        TextEntry::make('payment_notes')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make(__('codflow.order.section_notes'))
                    ->schema([
                        TextEntry::make('notes')->placeholder(__('codflow.order.no_notes'))->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => filled($record->notes))
                    ->columnSpanFull(),
                Section::make(__('codflow.review.section'))
                    ->schema([
                        TextEntry::make('review.link_sent_at')
                            ->label(__('codflow.review.link_sent_at'))
                            ->dateTime()
                            ->placeholder(__('codflow.review.link_not_sent')),
                        TextEntry::make('review.linkSender.name')
                            ->label(__('codflow.review.link_sent_by'))
                            ->placeholder('—'),
                        TextEntry::make('review.submitted_at')
                            ->label(__('codflow.review.submitted_at'))
                            ->dateTime()
                            ->placeholder(__('codflow.review.not_submitted')),
                        TextEntry::make('review.product_rating')
                            ->label(__('codflow.review.product_rating'))
                            ->formatStateUsing(fn ($state) => $state ? str_repeat('★', (int) $state).str_repeat('☆', 5 - (int) $state) : '—'),
                        TextEntry::make('review.service_rating')
                            ->label(__('codflow.review.service_rating'))
                            ->formatStateUsing(fn ($state) => $state ? str_repeat('★', (int) $state).str_repeat('☆', 5 - (int) $state) : '—'),
                        TextEntry::make('review.comment')
                            ->label(__('codflow.review.comment'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->review !== null)
                    ->columnSpanFull(),
            ]);
    }
}
