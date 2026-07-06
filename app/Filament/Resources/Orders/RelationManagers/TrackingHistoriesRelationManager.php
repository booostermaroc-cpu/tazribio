<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrackingHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'trackingHistories';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('codflow.relations.tracking_histories');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('status')->required(),
                Textarea::make('description'),
                Select::make('changed_by')
                    ->relationship('changedBy', 'name')
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')->badge(),
                TextColumn::make('description')->limit(50),
                TextColumn::make('changedBy.name')->label(Labels::field('changed_by')),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
