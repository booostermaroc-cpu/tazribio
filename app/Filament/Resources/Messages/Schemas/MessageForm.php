<?php

namespace App\Filament\Resources\Messages\Schemas;

use App\Filament\Support\Labels;
use App\Models\Conversation;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(Labels::section('message'))
                ->schema([
                    Select::make('conversation_id')
                        ->label(Labels::field('conversation'))
                        ->relationship('conversation', 'title', fn ($query) => $query->orderByDesc('updated_at'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('title')
                                ->label(Labels::field('conversation'))
                                ->required()
                                ->maxLength(191),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            return Conversation::query()->create([
                                'title' => $data['title'],
                                'created_by' => auth()->id(),
                            ])->id;
                        }),
                    Select::make('sender_id')
                        ->label(Labels::field('sender'))
                        ->relationship('sender', 'name', fn ($query) => $query->orderBy('name'))
                        ->searchable()
                        ->preload()
                        ->default(fn () => auth()->id())
                        ->required(),
                    Textarea::make('message')
                        ->label(Labels::field('message'))
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                    FileUpload::make('attachment')
                        ->label(Labels::field('attachment'))
                        ->directory('messages')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
