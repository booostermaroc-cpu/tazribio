<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')
                    ->label(Labels::field('logo'))
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name ?? 'C').'&background=ede9fe&color=7c3aed'),
                TextColumn::make('full_name')
                    ->label(Labels::field('full_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(Labels::field('phone'))
                    ->searchable(),
                TextColumn::make('city')
                    ->label(Labels::field('city'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_blacklisted')
                    ->label(Labels::field('is_blacklisted'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(Labels::field('created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('city')
                    ->label(Labels::field('city'))
                    ->options(fn () => \App\Models\Client::query()->whereNotNull('city')->distinct()->pluck('city', 'city')->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
