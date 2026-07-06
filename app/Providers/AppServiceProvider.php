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
use Illuminate\Support\Facades\Schema;
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
    }
}
