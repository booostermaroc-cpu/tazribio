<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Enums\ExpenseCategory;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(Labels::field('title'))
                    ->searchable(),
                EnumColumn::badge('category', ExpenseCategory::class),
                TextColumn::make('amount')
                    ->label(Labels::field('amount'))
                    ->money('MAD')
                    ->sortable(),
                TextColumn::make('date')
                    ->label(Labels::field('date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(Labels::field('created_by')),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(Labels::field('category'))
                    ->options(ExpenseCategory::options()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
