<?php

namespace App\Enums\Concerns;

use App\Filament\Support\CodflowLabels;
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
        return CodflowLabels::get(Str::after($this->enumTranslationKey(), 'codflow.'));
    }

    public function label(): string
    {
        return $this->translatedLabel(Str::headline($this->value));
    }
}
