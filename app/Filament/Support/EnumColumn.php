<?php

namespace App\Filament\Support;

use BackedEnum;
use Filament\Tables\Columns\TextColumn;

class EnumColumn
{
    /**
     * @param  class-string<BackedEnum&object{label(): string, color(): string}>  $enumClass
     */
    public static function badge(string $name, string $enumClass): TextColumn
    {
        return TextColumn::make($name)
            ->label(Labels::resolve($name) ?? Labels::field($name))
            ->badge()
            ->formatStateUsing(fn ($state) => ($enum = self::resolveEnum($state, $enumClass)) ? $enum->label() : '-')
            ->color(fn ($state) => ($enum = self::resolveEnum($state, $enumClass)) ? $enum->color() : 'gray');
    }

    /**
     * @param  class-string<BackedEnum&object{label(): string, color(): string}>  $enumClass
     */
    private static function resolveEnum(mixed $state, string $enumClass): ?object
    {
        if ($state instanceof $enumClass) {
            return $state;
        }

        if ($state === null || $state === '') {
            return null;
        }

        return $enumClass::tryFrom((string) $state);
    }
}
