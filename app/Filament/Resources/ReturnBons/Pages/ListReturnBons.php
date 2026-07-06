<?php

namespace App\Filament\Resources\ReturnBons\Pages;

use App\Filament\Resources\ReturnBons\ReturnBonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturnBons extends ListRecords
{
    protected static string $resource = ReturnBonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
