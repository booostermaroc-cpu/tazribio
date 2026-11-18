<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Filament\Support\AmeexNotifications;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\DeliveryIntegrationService;
use App\Support\CarrierStuckOrders;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('client.logo_url')
                    ->label(Labels::field('logo'))
                    ->circular()
                    ->toggleable(),
                TextColumn::make('order_number')->searchable()->sortable()->copyable(),
                TextColumn::make('client.full_name')->label(Labels::field('client'))->searchable()->sortable(),
                TextColumn::make('client.phone')->searchable()->label(Labels::field('phone')),
                TextColumn::make('shipment.tracking_number')->searchable()->label(Labels::field('tracking')),
                TextColumn::make('shipment.delivery_status')
                    ->label(Labels::field('delivery_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ShipmentStatus
                        ? $state->label()
                        : ShipmentStatus::tryFrom((string) $state)?->label())
                    ->color(fn ($state) => $state instanceof ShipmentStatus
                        ? $state->color()
                        : ShipmentStatus::tryFrom((string) $state)?->color())
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('shipment.ameex_last_status_name')
                    ->label(__('codflow.ui.ameex_last_status'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('city')->label(Labels::field('city'))->searchable()->sortable(),
                EnumColumn::badge('status', OrderStatus::class),
                EnumColumn::badge('payment_status', PaymentStatus::class),
                EnumColumn::badge('payment_method', PaymentMethod::class)
                    ->toggleable(),
                TextColumn::make('final_amount')->money('MAD')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')->options(OrderStatus::options()),
                SelectFilter::make('delivery_status')
                    ->label(Labels::field('delivery_status'))
                    ->options(ShipmentStatus::options())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return filled($value)
                            ? $query->whereHas('shipment', fn (Builder $s) => $s->where('delivery_status', $value))
                            : $query;
                    }),
                Filter::make('stuck_at_carrier')
                    ->label(__('codflow.filters.stuck_at_carrier'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => CarrierStuckOrders::applyTo($query)),
                SelectFilter::make('payment_status')->options(PaymentStatus::options()),
                SelectFilter::make('payment_method')->options(PaymentMethod::options()),
                SelectFilter::make('source')->options(OrderSource::options()),
                SelectFilter::make('city')
                    ->options(fn () => Order::query()->whereNotNull('city')->distinct()->pluck('city', 'city')->all()),
                Filter::make('created_at')
                    ->label(Labels::filter('created_period'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(Labels::field('from')),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(Labels::field('until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                Filter::make('delivery_company')
                    ->label(Labels::filter('delivery_company'))
                    ->form([
                        \Filament\Forms\Components\Select::make('delivery_company_id')
                            ->label(Labels::field('delivery_company'))
                            ->options(fn () => \App\Models\DeliveryCompany::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['delivery_company_id'] ?? null,
                            fn (Builder $q, $id) => $q->whereHas('shipment', fn ($s) => $s->where('delivery_company_id', $id))
                        );
                    }),
            ])
            ->recordActions([
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
                            $shipments = Shipment::query()
                                ->whereIn('order_id', $records->modelKeys())
                                ->get();

                            AmeexNotifications::notify(
                                app(DeliveryIntegrationService::class)->refreshMassTracking($shipments)
                            );
                        }),
                    BulkAction::make('ameexMassInfo')
                        ->label(__('codflow.delivery.ameex_mass_info'))
                        ->icon('heroicon-o-information-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $shipments = Shipment::query()
                                ->whereIn('order_id', $records->modelKeys())
                                ->get();

                            AmeexNotifications::notify(
                                app(DeliveryIntegrationService::class)->fetchMassInfo($shipments)
                            );
                        }),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
