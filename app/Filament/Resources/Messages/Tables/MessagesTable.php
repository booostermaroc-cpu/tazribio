<?php

namespace App\Filament\Resources\Messages\Tables;

use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('conversation.title')
                    ->label(Labels::field('conversation'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sender.name')
                    ->label(Labels::field('sender'))
                    ->searchable(),
                TextColumn::make('message')
                    ->label(Labels::field('message'))
                    ->searchable()
                    ->limit(60)
                    ->wrap(),
                IconColumn::make('attachment')
                    ->label(Labels::field('attachment'))
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus'),
                TextColumn::make('created_at')
                    ->label(Labels::field('created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('conversation_id')
                    ->label(Labels::field('conversation'))
                    ->relationship('conversation', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('sender_id')
                    ->label(Labels::field('sender'))
                    ->relationship('sender', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
