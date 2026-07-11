<?php

namespace App\Filament\Resources\ConfirmationTracking\Tables;

use App\Enums\OrderConfirmationAction;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use App\Models\OrderConfirmationLog;
use App\Services\ConfirmationTrackingService;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConfirmationTrackingTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('created_at')
                    ->label(Labels::field('created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('codflow.confirmation_tracking.agent'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('order.order_number')
                    ->label(Labels::field('order'))
                    ->searchable()
                    ->placeholder('—')
                    ->url(fn (OrderConfirmationLog $record): ?string => $record->order
                        ? OrderResource::getUrl('view', ['record' => $record->order])
                        : null),
                TextColumn::make('order.client.full_name')
                    ->label(Labels::field('client'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                EnumColumn::badge('action', OrderConfirmationAction::class),
                TextColumn::make('order.status')
                    ->label(Labels::field('status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof OrderStatus ? $state->label() : OrderStatus::tryFrom((string) $state)?->label())
                    ->color(fn ($state) => $state instanceof OrderStatus ? $state->color() : OrderStatus::tryFrom((string) $state)?->color())
                    ->toggleable(),
                TextColumn::make('notes')
                    ->label(__('codflow.confirmation_tracking.result'))
                    ->limit(50)
                    ->placeholder('—')
                    ->wrap(),
                IconColumn::make('process_complete')
                    ->label(__('codflow.confirmation_tracking.process_complete'))
                    ->boolean()
                    ->state(fn (OrderConfirmationLog $record): bool => $record->order
                        ? app(ConfirmationTrackingService::class)->processComplete($record->order)
                        : false)
                    ->visibleFrom('lg'),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('codflow.confirmation_tracking.agent'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('action')
                    ->label(__('codflow.confirmation_tracking.action'))
                    ->options(OrderConfirmationAction::options()),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(__('codflow.confirmation_tracking.from_date')),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(__('codflow.confirmation_tracking.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ]);
    }
}
