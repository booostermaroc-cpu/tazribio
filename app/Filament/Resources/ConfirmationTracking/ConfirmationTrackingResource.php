<?php

namespace App\Filament\Resources\ConfirmationTracking;

use App\Filament\Resources\ConfirmationTracking\Pages\ListConfirmationTracking;
use App\Filament\Resources\ConfirmationTracking\Tables\ConfirmationTrackingTable;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Filament\Support\Nav;
use App\Models\OrderConfirmationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConfirmationTrackingResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = OrderConfirmationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('team');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('confirmation_tracking');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user',
                'order' => fn ($query) => $query->withTrashed(),
                'order.client',
            ]);
    }

    public static function table(Table $table): Table
    {
        return ConfirmationTrackingTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConfirmationTracking::route('/'),
        ];
    }
}
