<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Enums\ExpenseCategory;
use App\Filament\Support\Labels;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label(Labels::field('title'))
                ->required()
                ->maxLength(191),
            TextInput::make('amount')
                ->label(Labels::field('amount'))
                ->numeric()
                ->prefix('MAD')
                ->required()
                ->minValue(0),
            Select::make('category')
                ->label(Labels::field('category'))
                ->options(ExpenseCategory::options())
                ->default(ExpenseCategory::Other->value)
                ->required()
                ->native(false),
            DatePicker::make('date')
                ->label(Labels::field('date'))
                ->required()
                ->default(now()),
            Textarea::make('notes')
                ->label(Labels::field('notes'))
                ->columnSpanFull(),
            Select::make('created_by')
                ->label(Labels::field('created_by'))
                ->relationship('creator', 'name', fn ($query) => $query->orderBy('name'))
                ->searchable()
                ->preload()
                ->default(fn () => auth()->id())
                ->required(),
        ]);
    }
}
