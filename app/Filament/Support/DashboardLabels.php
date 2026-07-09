<?php

namespace App\Filament\Support;

class DashboardLabels
{
    /** @param  array<string, string|int|float>  $replace */
    public static function get(string $key, array $replace = []): string
    {
        return CodflowLabels::dashboard($key, $replace);
    }

    /**
     * @param  array<string, int>  $distribution
     * @return array<string, int>
     */
    public static function labeledDistribution(array $distribution): array
    {
        return CodflowLabels::labeledDistribution($distribution);
    }
}
