<?php

namespace App\Providers;

use App\Filament\Support\Labels;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\ServiceProvider;

class FilamentFrenchLabelsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $applyLabel = function (object $component, ?string $name): void {
            if (! method_exists($component, 'label')) {
                return;
            }

            $label = Labels::resolve($name);

            if ($label) {
                $component->label($label);
            }
        };

        Select::configureUsing(function (Select $select): void {
            $select->preload();
        });

        Field::configureUsing(function (Field $field) use ($applyLabel): void {
            $applyLabel($field, $field->getName());
        });

        Column::configureUsing(function (Column $column) use ($applyLabel): void {
            $applyLabel($column, $column->getName());
        });

        Entry::configureUsing(function (Entry $entry) use ($applyLabel): void {
            $applyLabel($entry, $entry->getName());
        });

        SelectFilter::configureUsing(function (SelectFilter $filter) use ($applyLabel): void {
            $applyLabel($filter, $filter->getName());
            $filter->preload();
        });

        BaseFilter::configureUsing(function (BaseFilter $filter) use ($applyLabel): void {
            if ($filter instanceof SelectFilter) {
                return;
            }

            $name = $filter->getName();

            $filterLabel = match ($name) {
                'created_at' => Labels::filter('created_period'),
                'delivery_company' => Labels::filter('delivery_company'),
                'trashed' => Labels::filter('trashed'),
                default => Labels::resolve($name),
            };

            if ($filterLabel && method_exists($filter, 'label')) {
                $filter->label($filterLabel);
            }
        });
    }
}
