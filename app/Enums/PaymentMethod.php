<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum PaymentMethod: string
{
    use HasColorAndLabel;

    case Cod = 'cod';
    case CashPlus = 'cash_plus';
    case CihBankTransfer = 'cih_bank_transfer';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';


    public function color(): string
    {
        return match ($this) {
            self::Cod => 'success',
            self::CashPlus => 'info',
            self::CihBankTransfer => 'warning',
            self::BankTransfer => 'primary',
            self::Other => 'gray',
        };
    }

    public function requiresPaymentDetails(): bool
    {
        return $this !== self::Cod;
    }

    public function countsForCollectedProfit(): bool
    {
        return $this !== self::Cod;
    }

    /** @return list<string> */
    public static function collectedProfitValues(): array
    {
        return array_values(array_filter(
            array_map(fn (self $method): string => $method->value, self::cases()),
            fn (string $value): bool => $value !== self::Cod->value,
        ));
    }
}
