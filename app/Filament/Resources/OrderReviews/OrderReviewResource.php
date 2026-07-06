<?php

namespace App\Filament\Resources\OrderReviews;

use App\Filament\Resources\OrderReviews\Pages\ListOrderReviews;
use App\Filament\Resources\OrderReviews\Tables\OrderReviewsTable;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Filament\Support\Nav;
use App\Models\OrderReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderReviewResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = OrderReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('crm');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('order_reviews');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->whereNotNull('submitted_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['order.client', 'linkSender'])
            ->whereNotNull('submitted_at');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return OrderReviewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderReviews::route('/'),
        ];
    }
}
