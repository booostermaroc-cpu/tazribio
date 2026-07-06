<?php

namespace App\Filament\Resources\Complaints\Tables;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ComplaintsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')
                    ->label(Labels::field('subject'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('order.order_number')
                    ->label(Labels::field('order'))
                    ->searchable()
                    ->url(fn ($record) => $record->order_id
                        ? OrderResource::getUrl('view', ['record' => $record->order_id])
                        : null),
                TextColumn::make('client.full_name')
                    ->label(Labels::field('client'))
                    ->searchable(),
                EnumColumn::badge('status', ComplaintStatus::class),
                EnumColumn::badge('priority', ComplaintPriority::class),
                TextColumn::make('assignee.name')
                    ->label(Labels::field('assigned_to'))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(Labels::field('created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(Labels::field('status'))
                    ->options(ComplaintStatus::options()),
                SelectFilter::make('priority')
                    ->label(Labels::field('priority'))
                    ->options(ComplaintPriority::options()),
                SelectFilter::make('assigned_to')
                    ->label(Labels::field('assigned_to'))
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
