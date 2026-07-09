<?php

namespace App\Filament\Support;

use Illuminate\Support\Str;

trait HasCodflowResourceLabels
{
    protected static function codflowLabelKey(): string
    {
        return (string) Str::of(class_basename(static::class))
            ->beforeLast('Resource')
            ->snake();
    }

    public static function getModelLabel(): string
    {
        return CodflowLabels::get(static::codflowTranslationKey('singular'));
    }

    public static function getPluralModelLabel(): string
    {
        return CodflowLabels::get(static::codflowTranslationKey('plural'));
    }

    public static function hasTitleCaseModelLabel(): bool
    {
        return false;
    }

    protected static function codflowTranslationKey(string $form): string
    {
        return 'codflow.models.'.static::codflowLabelKey().'.'.$form;
    }
}
