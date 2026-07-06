<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum ExpenseCategory: string
{
    use HasColorAndLabel;

    case Water = 'water';
    case Electricity = 'electricity';
    case Rent = 'rent';
    case Supplier = 'supplier';
    case Salary = 'salary';
    case Other = 'other';

    public function label(): string
    {
        return __($this->enumTranslationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Water => 'info',
            self::Electricity => 'warning',
            self::Rent => 'primary',
            self::Supplier => 'gray',
            self::Salary => 'success',
            self::Other => 'danger',
        };
    }
}
