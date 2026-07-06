<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Filament\Support\Labels;
use App\Rules\MoroccanPhone;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('logo')
                    ->label(Labels::field('logo'))
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->directory('clients')
                    ->visibility('public')
                    ->imageEditor()
                    ->columnSpanFull(),
                TextInput::make('full_name')
                    ->label(Labels::field('full_name'))
                    ->required()
                    ->maxLength(191),
                TextInput::make('phone')
                    ->label(Labels::field('phone'))
                    ->tel()
                    ->required()
                    ->maxLength(191)
                    ->rules([new MoroccanPhone]),
                TextInput::make('second_phone')
                    ->label(Labels::field('second_phone'))
                    ->tel()
                    ->maxLength(191)
                    ->rules([new MoroccanPhone]),
                TextInput::make('city')
                    ->label(Labels::field('city'))
                    ->maxLength(191),
                Textarea::make('address')
                    ->label(Labels::field('address'))
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label(Labels::field('notes'))
                    ->columnSpanFull(),
                Toggle::make('is_blacklisted')
                    ->label(Labels::field('is_blacklisted')),
            ]);
    }
}
