<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\DashboardLabels;
use App\Filament\Support\EnumColumn;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Filament\Support\Labels;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrdersWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 7,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading(DashboardLabels::get('charts.latest_orders'))
            ->query(
                Order::query()
                    ->with(['client'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                ImageColumn::make('client.logo_url')
                    ->label(Labels::field('logo'))
                    ->circular()
                    ->toggleable(),
                TextColumn::make('order_number')
                    ->label(DashboardLabels::get('table.order'))
                    ->formatStateUsing(fn (string $state): string => '#'.$state)
                    ->color('primary')
                    ->weight('bold')
                    ->icon('heroicon-o-shopping-bag'),
                TextColumn::make('client.full_name')
                    ->label(DashboardLabels::get('table.client')),
                TextColumn::make('city')
                    ->label(DashboardLabels::get('table.city'))
                    ->color('gray'),
                TextColumn::make('final_amount')
                    ->label(DashboardLabels::get('table.amount'))
                    ->money('MAD')
                    ->weight('semibold'),
                EnumColumn::badge('status', OrderStatus::class),
                TextColumn::make('created_at')
                    ->label(DashboardLabels::get('table.date'))
                    ->since()
                    ->color('gray'),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->paginated(false);
    }
}
