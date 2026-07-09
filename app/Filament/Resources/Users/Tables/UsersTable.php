<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use App\Services\CommissionService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(Labels::field('name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label(Labels::field('email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label(Labels::field('phone'))
                    ->searchable()
                    ->placeholder('—'),
                EnumColumn::badge('role', UserRole::class)
                    ->label(Labels::field('role')),
                TextColumn::make('unpaid_commissions')
                    ->label(__('codflow.users.unpaid_commission_total'))
                    ->state(fn ($record) => app(CommissionService::class)->unpaidTotalForUser($record))
                    ->money('MAD')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->withSum([
                            'commissions as unpaid_sum' => fn ($q) => $q->whereIn('status', ['pending', 'approved']),
                        ], 'amount')->orderBy('unpaid_sum', $direction);
                    }),
                TextColumn::make('confirmed_orders_count')
                    ->label(__('codflow.users.confirmed_orders_count'))
                    ->state(fn ($record) => app(CommissionService::class)->confirmedOrdersCount($record))
                    ->sortable(query: function ($query, string $direction) {
                        return $query->withCount([
                            'confirmedOrders as confirmed_orders_count',
                        ])->orderBy('confirmed_orders_count', $direction);
                    }),
                IconColumn::make('is_active')
                    ->label(Labels::field('is_active'))
                    ->boolean(),
                TextColumn::make('allowed_resources')
                    ->label(__('codflow.users.allowed_pages'))
                    ->formatStateUsing(fn ($state): string => is_array($state) && $state !== []
                        ? (string) count($state)
                        : __('codflow.users.default_permissions'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(Labels::field('created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')->options(UserRole::options()),
                TernaryFilter::make('is_active')
                    ->label(Labels::field('is_active')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
