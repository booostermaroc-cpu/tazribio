<?php

namespace App\Filament\Resources\Complaints\Schemas;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Filament\Support\Labels;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComplaintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(Labels::section('order'))
                ->schema([
                    Select::make('order_id')
                        ->label(Labels::field('order'))
                        ->relationship('order', 'order_number', fn ($query) => $query->orderByDesc('created_at'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set): void {
                            if (! $state) {
                                return;
                            }

                            $clientId = Order::query()->whereKey($state)->value('client_id');

                            if ($clientId) {
                                $set('client_id', $clientId);
                            }
                        }),
                    Select::make('client_id')
                        ->label(Labels::field('client'))
                        ->relationship('client', 'full_name', fn ($query) => $query->orderBy('full_name'))
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(Labels::section('complaint'))
                ->schema([
                    TextInput::make('subject')
                        ->label(Labels::field('subject'))
                        ->required()
                        ->maxLength(191)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label(Labels::field('description'))
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                    Select::make('status')
                        ->label(Labels::field('status'))
                        ->options(ComplaintStatus::options())
                        ->default(ComplaintStatus::Open->value)
                        ->required(),
                    Select::make('priority')
                        ->label(Labels::field('priority'))
                        ->options(ComplaintPriority::options())
                        ->default(ComplaintPriority::Medium->value)
                        ->required(),
                    Select::make('assigned_to')
                        ->label(Labels::field('assigned_to'))
                        ->relationship('assignee', 'name', fn ($query) => $query->orderBy('name'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }
}
