<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\ComplaintsRelationManager;
use App\Filament\Resources\Orders\RelationManagers\ConfirmationLogsRelationManager;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Orders\RelationManagers\ShipmentsRelationManager;
use App\Filament\Resources\Orders\RelationManagers\TrackingHistoriesRelationManager;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Enums\OrderStatus;
use App\Filament\Support\DashboardMetrics;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Filament\Support\Nav;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrderResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('sales');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('orders');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = DashboardMetrics::newOrdersBadgeCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'order_number',
            'client.full_name',
            'client.phone',
            'shipment.tracking_number',
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('codflow.ui.search_client') => $record->client?->full_name,
            __('codflow.ui.search_phone') => $record->client?->phone,
            __('codflow.ui.search_status') => $record->status?->label(),
        ];
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $query->orWhereHas('items.product', fn (Builder $productQuery) => $productQuery->where('sku', 'like', "%{$search}%"));
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['client', 'shipment', 'items.product']);
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            ShipmentsRelationManager::class,
            TrackingHistoriesRelationManager::class,
            ConfirmationLogsRelationManager::class,
            ComplaintsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
