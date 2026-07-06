<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum UserRole: string
{
    use HasColorAndLabel;

    case Admin = 'admin';
    case Manager = 'manager';
    case Agent = 'agent';
    case DeliveryAgent = 'delivery_agent';
    case StockManager = 'stock_manager';
    case Finance = 'finance';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Manager => 'warning',
            self::Agent => 'info',
            self::DeliveryAgent => 'success',
            self::StockManager => 'primary',
            self::Finance => 'gray',
        };
    }
}
