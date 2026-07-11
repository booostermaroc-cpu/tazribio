<?php

namespace App\Support;

use App\Filament\Support\CodflowLabels;

enum AppResource: string
{
    case Dashboard = 'dashboard';
    case Orders = 'orders';
    case Products = 'products';
    case Clients = 'clients';
    case Users = 'users';
    case Settings = 'settings';
    case Shipments = 'shipments';
    case DeliveryCompanies = 'delivery_companies';
    case Invoices = 'invoices';
    case PaymentPlannings = 'payment_plannings';
    case ReturnBons = 'return_bons';
    case PickupRequests = 'pickup_requests';
    case Complaints = 'complaints';
    case Messages = 'messages';
    case Expenses = 'expenses';
    case Warehouses = 'warehouses';
    case StockMovements = 'stock_movements';
    case ConfirmationTracking = 'confirmation_tracking';
    case OrderReviews = 'order_reviews';

    public function label(): string
    {
        return CodflowLabels::get("nav.{$this->value}");
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $resource) => [$resource->value => $resource->label()])
            ->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
