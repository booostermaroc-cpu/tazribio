<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\CommissionStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('codflow.users.confirmed_orders_commissions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['order.client']))
            ->columns([
                TextColumn::make('order.order_number')
                    ->label(Labels::field('order_number'))
                    ->searchable()
                    ->url(fn ($record) => $record->order_id
                        ? OrderResource::getUrl('view', ['record' => $record->order_id])
                        : null),
                TextColumn::make('order.client.full_name')
                    ->label(Labels::field('client'))
                    ->placeholder('—'),
                TextColumn::make('order.city')
                    ->label(Labels::field('city'))
                    ->placeholder('—'),
                TextColumn::make('order.final_amount')
                    ->label(Labels::field('final_amount'))
                    ->money('MAD'),
                TextColumn::make('amount')
                    ->label(__('codflow.users.commission_amount'))
                    ->money('MAD')
                    ->weight('bold'),
                EnumColumn::badge('status', CommissionStatus::class)
                    ->label(Labels::field('status')),
                TextColumn::make('calculated_at')
                    ->label(__('codflow.users.commission_calculated_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label(__('codflow.users.commission_paid_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->defaultSort('calculated_at', 'desc')
            ->recordActions([
                Action::make('markPaid')
                    ->label(__('codflow.users.mark_commission_paid'))
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedBanknotes)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== CommissionStatus::Paid
                        && $record->status !== CommissionStatus::Cancelled)
                    ->action(function ($record): void {
                        $record->update([
                            'status' => CommissionStatus::Paid,
                            'paid_at' => now(),
                        ]);
                    }),
            ])
            ->paginated([10, 25, 50]);
    }
}
