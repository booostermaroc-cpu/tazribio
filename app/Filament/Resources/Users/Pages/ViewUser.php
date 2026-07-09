<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\CommissionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $synced = app(CommissionService::class)->syncCommissionsForUser($this->getRecord());

        if ($synced > 0) {
            Notification::make()
                ->title(__('codflow.users.commissions_synced_title'))
                ->body(__('codflow.users.commissions_synced_body', ['count' => $synced]))
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllPaid')
                ->label(__('codflow.users.mark_all_commissions_paid'))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('codflow.users.mark_all_commissions_paid'))
                ->modalDescription(__('codflow.users.mark_all_commissions_paid_confirm'))
                ->action(function (): void {
                    $count = app(CommissionService::class)->markAllUnpaidAsPaid($this->getRecord());

                    Notification::make()
                        ->title(__('codflow.users.commissions_marked_paid_title'))
                        ->body(__('codflow.users.commissions_marked_paid_body', ['count' => $count]))
                        ->success()
                        ->send();

                    $this->refreshFormData(['commissions']);
                }),
            EditAction::make(),
        ];
    }
}
