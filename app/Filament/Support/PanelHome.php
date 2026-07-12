<?php

namespace App\Filament\Support;

use App\Filament\Pages\AiPage;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\DemoPage;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\ConfirmationTracking\ConfirmationTrackingResource;
use App\Filament\Resources\DeliveryCompanies\DeliveryCompanyResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Messages\MessageResource;
use App\Filament\Resources\OrderReviews\OrderReviewResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\PaymentPlannings\PaymentPlanningResource;
use App\Filament\Resources\PickupRequests\PickupRequestResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\ReturnBons\ReturnBonResource;
use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Support\AppResource;
use App\Support\RolePermission;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;

final class PanelHome
{
    /** @var array<string, class-string<Page|Resource>> */
    private const TARGETS = [
        'dashboard' => Dashboard::class,
        'ai' => AiPage::class,
        'demo' => DemoPage::class,
        'orders' => OrderResource::class,
        'products' => ProductResource::class,
        'clients' => ClientResource::class,
        'users' => UserResource::class,
        'settings' => SettingResource::class,
        'shipments' => ShipmentResource::class,
        'delivery_companies' => DeliveryCompanyResource::class,
        'invoices' => InvoiceResource::class,
        'payment_plannings' => PaymentPlanningResource::class,
        'return_bons' => ReturnBonResource::class,
        'pickup_requests' => PickupRequestResource::class,
        'complaints' => ComplaintResource::class,
        'messages' => MessageResource::class,
        'expenses' => ExpenseResource::class,
        'warehouses' => WarehouseResource::class,
        'stock_movements' => StockMovementResource::class,
        'confirmation_tracking' => ConfirmationTrackingResource::class,
        'order_reviews' => OrderReviewResource::class,
    ];

    public static function url(): string
    {
        $panel = Filament::getPanel();
        $user = auth()->user();

        if ($user === null) {
            return $panel->getLoginUrl() ?? url($panel->getPath());
        }

        foreach (AppResource::cases() as $resource) {
            if (! RolePermission::canAccessResource($user, $resource->value)) {
                continue;
            }

            $class = self::TARGETS[$resource->value] ?? null;

            if ($class === null) {
                continue;
            }

            if (is_subclass_of($class, Resource::class)) {
                if ($class::canViewAny()) {
                    return $class::getUrl('index');
                }

                continue;
            }

            if ($class::canAccess()) {
                return $class::getUrl();
            }
        }

        $redirect = $panel->getRedirectUrl();

        if (filled($redirect)) {
            return $redirect;
        }

        return url($panel->getPath());
    }
}
