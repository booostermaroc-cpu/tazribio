<?php

namespace App\Filament\Pages;

use App\Exceptions\ReturnScanException;
use App\Filament\Resources\ReturnBons\ReturnBonResource;
use App\Services\ReturnScanService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ScanReturnPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.scan-return';

    public static function getNavigationGroup(): ?string
    {
        return \App\Filament\Support\Nav::group('sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('codflow.returns.scan_page');
    }

    public ?string $scanCode = '';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scan')
                ->label(__('codflow.returns.scan_action'))
                ->icon(Heroicon::OutlinedMagnifyingGlassCircle)
                ->form([
                    TextInput::make('scanCode')
                        ->label(__('codflow.returns.scan_input'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->processScan($data['scanCode']);
                }),
        ];
    }

    public function processScan(string $code): void
    {
        try {
            $returnBon = app(ReturnScanService::class)->processScan($code, auth()->id());

            Notification::make()
                ->title(__('codflow.returns.scan_success'))
                ->body(__('codflow.returns.scan_success_body', ['number' => $returnBon->return_number]))
                ->success()
                ->send();

            $this->redirect(ReturnBonResource::getUrl('edit', ['record' => $returnBon]));
        } catch (ReturnScanException $exception) {
            Notification::make()
                ->title(__('codflow.returns.scan_error'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
