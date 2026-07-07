<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
