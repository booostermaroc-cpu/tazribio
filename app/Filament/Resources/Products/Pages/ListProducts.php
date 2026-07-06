<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Support\Labels;
use App\Exports\ProductsExport;
use App\Exports\ProductsTemplateExport;
use App\Filament\Resources\Products\ProductResource;
use App\Imports\ProductsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(Labels::action('export_excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn() => Excel::download(new ProductsExport, 'products-' . now()->format('Y-m-d') . '.xlsx')),
            Action::make('template')
                ->label(Labels::action('download_template'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->action(fn() => Excel::download(new ProductsTemplateExport, 'products-import-template.xlsx')),
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
                    Excel::import(new ProductsImport, $path);

                    Notification::make()
                        ->title(__('codflow.ui.import_completed'))
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
