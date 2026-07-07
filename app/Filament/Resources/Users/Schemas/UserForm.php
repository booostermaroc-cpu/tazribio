<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\CommissionApplyOn;
use App\Enums\CommissionType;
use App\Enums\UserRole;
use App\Filament\Support\Labels;
use App\Support\AppResource;
use App\Support\RolePermission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(Labels::section('user'))
                    ->schema([
                        TextInput::make('name')
                            ->label(Labels::field('name'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('email')
                            ->label(Labels::field('email'))
                            ->email()
                            ->required()
                            ->maxLength(191)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => __('codflow.validation.email_unique'),
                            ]),
                        TextInput::make('phone')
                            ->label(Labels::field('phone'))
                            ->tel()
                            ->maxLength(191),
                        Select::make('role')
                            ->label(Labels::field('role'))
                            ->options(UserRole::options())
                            ->default(UserRole::Agent->value)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $set(
                                    'allowed_resources',
                                    RolePermission::defaultResourcesForRole(UserRole::from($state)),
                                );
                            }),
                        Toggle::make('is_active')
                            ->label(Labels::field('is_active'))
                            ->default(true),
                        TextInput::make('password')
                            ->label(Labels::field('password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(__('codflow.users.password_help')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(Labels::section('permissions'))
                    ->description(__('codflow.users.allowed_pages_help'))
                    ->schema([
                        CheckboxList::make('allowed_resources')
                            ->label(__('codflow.users.allowed_pages'))
                            ->options(AppResource::options())
                            ->columns(2)
                            ->bulkToggleable()
                            ->default(fn () => RolePermission::defaultResourcesForRole(UserRole::Agent))
                            ->afterStateHydrated(function (?array $state, Set $set, Get $get): void {
                                if (is_array($state) && $state !== []) {
                                    return;
                                }

                                $role = UserRole::tryFrom((string) ($get('role') ?? UserRole::Agent->value)) ?? UserRole::Agent;

                                $set('allowed_resources', RolePermission::defaultResourcesForRole($role));
                            })
                            ->helperText(fn (Get $get): string => UserRole::tryFrom((string) $get('role')) === UserRole::Admin
                                ? __('codflow.users.admin_permissions_help')
                                : __('codflow.users.allowed_pages_hint')),
                    ])
                    ->visible(fn (): bool => auth()->user()?->role === UserRole::Admin)
                    ->columnSpanFull(),
                Section::make(Labels::section('commission'))
                    ->schema([
                        Select::make('confirmation_commission_type')
                            ->label(Labels::field('commission_type'))
                            ->options(CommissionType::options())
                            ->default(CommissionType::None->value)
                            ->required(),
                        TextInput::make('confirmation_commission_value')
                            ->label(Labels::field('commission_value'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Select::make('apply_commission_on')
                            ->label(Labels::field('commission_apply_on'))
                            ->options(CommissionApplyOn::options())
                            ->default(CommissionApplyOn::Delivered->value)
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
