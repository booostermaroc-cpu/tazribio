<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Schemas\Components\Section;
use Livewire\Livewire;

class OrderFulfillmentSection
{
    public static function make(): Section
    {
        return Section::make(__('codflow.order.fulfillment_process'))
            ->schema([
                FormActions::make(function (): array {
                    $livewire = Livewire::current();

                    if (! is_object($livewire) || ! method_exists($livewire, 'getOrderFulfillmentButtonActions')) {
                        return [];
                    }

                    $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

                    return array_map(
                        fn (Action $action): Action => $action
                            ->button()
                            ->record($record)
                            ->extraAttributes(['class' => 'codflow-fulfillment-action-btn']),
                        $livewire->getOrderFulfillmentButtonActions(),
                    );
                })
                    ->fullWidth()
                    ->extraAttributes(['class' => 'codflow-fulfillment-actions-grid']),
            ])
            ->visible(function (): bool {
                $livewire = Livewire::current();

                return is_object($livewire)
                    && method_exists($livewire, 'fulfillmentActionsVisible')
                    && $livewire->fulfillmentActionsVisible();
            })
            ->columnSpanFull();
    }
}
