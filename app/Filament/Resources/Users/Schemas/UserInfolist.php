<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\CommissionApplyOn;
use App\Enums\CommissionType;
use App\Enums\UserRole;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use App\Services\CommissionService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Section::make(Labels::section('user'))
                            ->schema([
                                TextEntry::make('name')->weight('bold'),
                                TextEntry::make('email')->copyable(),
                                TextEntry::make('phone')->placeholder('—'),
                                TextEntry::make('role')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state?->label())
                                    ->color(fn ($state) => $state?->color()),
                                TextEntry::make('is_active')
                                    ->label(Labels::field('is_active'))
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non'),
                            ])
                            ->columnSpan(1),
                        Section::make(Labels::section('commission'))
                            ->schema([
                                TextEntry::make('confirmation_commission_type')
                                    ->label(Labels::field('commission_type'))
                                    ->formatStateUsing(fn ($state, $record) => $record->confirmation_commission_type?->label()
                                        ?? CommissionType::None->label()),
                                TextEntry::make('confirmation_commission_value')
                                    ->label(Labels::field('commission_value'))
                                    ->suffix(' MAD')
                                    ->placeholder('—'),
                                TextEntry::make('apply_commission_on')
                                    ->label(Labels::field('commission_apply_on'))
                                    ->formatStateUsing(fn ($state, $record) => $record->apply_commission_on?->label()
                                        ?? CommissionApplyOn::Delivered->label()),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
                Section::make(__('codflow.users.commission_summary'))
                    ->schema([
                        TextEntry::make('confirmed_orders_count')
                            ->label(__('codflow.users.confirmed_orders_count'))
                            ->state(fn ($record) => app(CommissionService::class)->confirmedOrdersCount($record)),
                        TextEntry::make('unpaid_commission_total')
                            ->label(__('codflow.users.unpaid_commission_total'))
                            ->state(fn ($record) => number_format(app(CommissionService::class)->unpaidTotalForUser($record), 2, ',', ' ').' MAD')
                            ->weight('bold')
                            ->color('warning'),
                        TextEntry::make('paid_commission_total')
                            ->label(__('codflow.users.paid_commission_total'))
                            ->state(fn ($record) => number_format(app(CommissionService::class)->paidTotalForUser($record), 2, ',', ' ').' MAD')
                            ->color('success'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
