<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Support\Labels;
use App\Exports\OrdersExport;
use App\Exports\OrdersTemplateExport;
use App\Filament\Resources\Orders\OrderResource;
use App\Imports\OrdersImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(Labels::action('export_excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => Excel::download(new OrdersExport, 'orders-'.now()->format('Y-m-d').'.xlsx')),
            Action::make('template')
                ->label(Labels::action('download_template'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->action(fn () => Excel::download(new OrdersTemplateExport, 'orders-import-template.xlsx')),
            Action::make('import')
                ->label(Labels::action('import_excel'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->form([
                    FileUpload::make('file')
                        ->label(Labels::field('excel_file'))
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('public')->path($data['file']);
                    Excel::import(new OrdersImport, $path);

                    Notification::make()
                        ->title(__('codflow.ui.import_completed'))
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
