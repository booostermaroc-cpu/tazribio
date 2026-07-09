<?php

namespace App\Filament\Support;

use BackedEnum;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

final class CodflowLabels
{
    protected static string $fallbackLocale = 'fr';

    /** @param  array<string, string|int|float>  $replace */
    public static function get(string $key, array $replace = [], ?string $hardFallback = null): string
    {
        $fullKey = str_starts_with($key, 'codflow.') ? $key : "codflow.{$key}";

        $translated = __($fullKey, $replace);

        if ($translated !== $fullKey) {
            return $translated;
        }

        $fr = Lang::get($fullKey, $replace, self::$fallbackLocale);

        if (is_string($fr) && $fr !== $fullKey) {
            return $fr;
        }

        if ($hardFallback !== null) {
            foreach ($replace as $search => $value) {
                $hardFallback = str_replace(':'.$search, (string) $value, $hardFallback);
            }

            return $hardFallback;
        }

        return Str::headline(str_replace(['.', '_'], ' ', Str::afterLast($fullKey, '.')));
    }

    public static function field(string $key): string
    {
        return static::get("fields.{$key}");
    }

    public static function section(string $key): string
    {
        return static::get("sections.{$key}");
    }

    public static function action(string $key): string
    {
        return static::get("actions.{$key}");
    }

    public static function filter(string $key): string
    {
        return static::get("filters.{$key}");
    }

    public static function order(string $key, array $replace = []): string
    {
        return static::get("order.{$key}", $replace);
    }

    public static function delivery(string $key, array $replace = [], ?string $hardFallback = null): string
    {
        return static::get("delivery.{$key}", $replace, $hardFallback);
    }

    public static function dashboard(string $key, array $replace = []): string
    {
        return static::get("dashboard.{$key}", $replace);
    }

    public static function enum(BackedEnum $case): string
    {
        $group = Str::snake(class_basename($case));

        return static::get("enums.{$group}.{$case->value}");
    }

    /**
     * @param  array<string, int>  $distribution
     * @return array<string, int>
     */
    public static function labeledDistribution(array $distribution, string $prefix = 'distribution'): array
    {
        $labeled = [];

        foreach ($distribution as $key => $count) {
            $labeled[static::get("dashboard.{$prefix}.{$key}")] = $count;
        }

        return $labeled;
    }
}
