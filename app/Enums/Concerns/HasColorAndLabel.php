<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

trait HasColorAndLabel
{
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    protected function enumTranslationKey(): string
    {
        $group = Str::snake(class_basename(static::class));

        return "codflow.enums.{$group}.{$this->value}";
    }

    protected function translatedLabel(string $fallback): string
    {
        $translated = __($this->enumTranslationKey());

        return $translated === $this->enumTranslationKey() ? $fallback : $translated;
    }
}
