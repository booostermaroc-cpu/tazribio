<?php

namespace App\Filament\Resources\ReturnBons\Pages;

use App\Filament\Resources\ReturnBons\ReturnBonResource;
use App\Models\ReturnBon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnBon extends EditRecord
{
    protected static string $resource = ReturnBonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label(__('codflow.order.download_pdf'))
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (ReturnBon $record) => route('documents.return-bon', $record))
                ->openUrlInNewTab(),
            Action::make('printPdf')
                ->label(__('codflow.order.print'))
                ->icon('heroicon-o-printer')
                ->url(fn (ReturnBon $record) => route('documents.return-bon', ['returnBon' => $record, 'print' => 1]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
