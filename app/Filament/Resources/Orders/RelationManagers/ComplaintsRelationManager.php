<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Filament\Support\Labels;
use App\Filament\Support\EnumColumn;
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

class ComplaintsRelationManager extends RelationManager
{
    protected static string $relationship = 'complaints';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('codflow.relations.complaints');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('subject')->required(),
                Textarea::make('description')->required()->columnSpanFull(),
                Select::make('status')
                    ->options(ComplaintStatus::options())
                    ->default(ComplaintStatus::Open->value)
                    ->required(),
                Select::make('priority')
                    ->options(ComplaintPriority::options())
                    ->default(ComplaintPriority::Medium->value)
                    ->required(),
                Select::make('assigned_to')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')->searchable(),
                EnumColumn::badge('status', ComplaintStatus::class),
                EnumColumn::badge('priority', ComplaintPriority::class),
                TextColumn::make('assignee.name')->label(Labels::field('assigned_to')),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $order = $this->getOwnerRecord();

                        $data['order_id'] = $order->id;
                        $data['client_id'] = $order->client_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
