<?php

namespace App\Filament\Support;

class Nav
{
    public static function group(string $key): string
    {
        return __("codflow.nav.groups.{$key}");
    }

    public static function label(string $key): string
    {
        return __("codflow.nav.{$key}");
    }
}
