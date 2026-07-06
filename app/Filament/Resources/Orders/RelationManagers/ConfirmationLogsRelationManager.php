<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Enums\OrderConfirmationAction;

class ConfirmationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'confirmationLogs';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('codflow.relations.confirmation_logs');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('created_at')
                    ->label(Labels::field('created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('codflow.confirmation_tracking.agent'))
                    ->placeholder('—'),
                EnumColumn::badge('action', OrderConfirmationAction::class),
                TextColumn::make('notes')
                    ->label(__('codflow.confirmation_tracking.result'))
                    ->limit(50)
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
