<?php

namespace App\Filament\Support;

class Nav
{
    public static function group(string $key): string
    {
        return CodflowLabels::get("nav.groups.{$key}");
    }

    public static function label(string $key): string
    {
        return CodflowLabels::get("nav.{$key}");
    }
}
