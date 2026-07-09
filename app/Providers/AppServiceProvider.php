<?php

namespace App\Providers;

use App\Models\Complaint;
use App\Models\Order;
use App\Models\ReturnBon;
use App\Models\Shipment;
use App\Observers\ComplaintObserver;
use App\Observers\OrderObserver;
use App\Observers\ReturnBonObserver;
use App\Observers\ShipmentObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Order::observe(OrderObserver::class);
        Shipment::observe(ShipmentObserver::class);
        ReturnBon::observe(ReturnBonObserver::class);
        Complaint::observe(ComplaintObserver::class);

        Gate::before(function ($user, $ability) {
            if ($user?->role?->value === 'admin') {
                return true;
            }

            return null;
        });

        Lang::handleMissingKeysUsing(function (string $key, array $replace, string $locale): string {
            if (! str_starts_with($key, 'codflow.')) {
                return $key;
            }

            if ($locale !== 'fr') {
                $fr = Lang::get($key, $replace, 'fr');

                if ($fr !== $key) {
                    return $fr;
                }
            }

            return Str::headline(str_replace(['.', '_'], ' ', Str::afterLast($key, '.')));
        });
    }
}
