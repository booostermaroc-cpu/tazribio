<?php

namespace App\Filament\Support;

use Illuminate\Support\Str;

class Labels
{
    /** @var array<string, string> */
    protected static array $pathMap = [
        'deliveryCompany.name' => 'carrier',
        'order.order_number' => 'order_number',
        'order_id' => 'order',
        'client_id' => 'client',
        'client.full_name' => 'client',
        'client.phone' => 'phone',
        'client.logo_url' => 'logo',
        'product.name' => 'product',
        'product.sku' => 'sku',
        'product.ameex_reference' => 'ameex_reference',
        'product_id' => 'product',
        'assignee.name' => 'assigned_to',
        'creator.name' => 'created_by',
        'sender.name' => 'sender',
        'sender_id' => 'sender',
        'recipient.name' => 'recipient',
        'recipient_id' => 'recipient',
        'changedBy.name' => 'changed_by',
        'user.name' => 'user',
        'user_id' => 'user',
        'warehouse_id' => 'warehouse',
        'warehouse.name' => 'warehouse',
        'conversation_id' => 'conversation',
        'conversation.title' => 'conversation',
        'delivery_company_id' => 'delivery_company',
        'shipment.tracking_number' => 'tracking',
        'image_url' => 'image',
    ];

    public static function field(string $key): string
    {
        return CodflowLabels::field($key);
    }

    public static function section(string $key): string
    {
        return CodflowLabels::section($key);
    }

    public static function action(string $key): string
    {
        return CodflowLabels::action($key);
    }

    public static function filter(string $key): string
    {
        return CodflowLabels::filter($key);
    }

    public static function has(string $key): bool
    {
        return \Illuminate\Support\Facades\Lang::has("codflow.fields.{$key}", app()->getLocale())
            || \Illuminate\Support\Facades\Lang::has("codflow.fields.{$key}", 'fr');
    }

    public static function resolve(?string $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        if (isset(static::$pathMap[$name])) {
            return static::field(static::$pathMap[$name]);
        }

        $key = Str::contains($name, '.') ? Str::afterLast($name, '.') : $name;

        if (static::has($key)) {
            return static::field($key);
        }

        if (static::has($name)) {
            return static::field($name);
        }

        return null;
    }
}
