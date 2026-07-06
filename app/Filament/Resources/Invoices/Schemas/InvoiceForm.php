<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('invoice_number')->default(fn () => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)))->required()->unique(ignoreRecord: true),
            Select::make('order_id')->relationship('order', 'order_number')->searchable()->preload()->required(),
            TextInput::make('amount')->numeric()->prefix('MAD')->required(),
            Select::make('status')->options(InvoiceStatus::options())->default(InvoiceStatus::Pending->value)->required(),
            DateTimePicker::make('paid_at'),
        ]);
    }
}
