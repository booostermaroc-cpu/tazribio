<?php

namespace App\Models;

use App\Enums\DeliveryProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryCompany extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'phone',
        'api_url',
        'api_base_url',
        'api_token',
        'api_username',
        'api_password',
        'api_settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'provider' => DeliveryProvider::class,
            'api_settings' => 'array',
        ];
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function paymentPlannings(): HasMany
    {
        return $this->hasMany(PaymentPlanning::class);
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }
}
