<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Support\Labels;
use App\Exports\StockMovementsExport;
use App\Filament\Resources\StockMovements\StockMovementResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(Labels::action('export_excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => Excel::download(new StockMovementsExport, 'stock-movements-'.now()->format('Y-m-d').'.xlsx')),
            CreateAction::make(),
        ];
    }
}
