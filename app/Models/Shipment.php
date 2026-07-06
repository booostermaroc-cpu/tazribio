<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_company_id',
        'tracking_number',
        'delivery_status',
        'delivery_date',
        'return_reason',
        'last_tracking_update',
        'ameex_delivery_note_ref',
        'ameex_parcel_code',
        'ameex_last_status',
        'ameex_last_status_name',
        'ameex_last_sub_status',
        'ameex_raw_response',
    ];

    protected function casts(): array
    {
        return [
            'delivery_status' => ShipmentStatus::class,
            'delivery_date' => 'date',
            'last_tracking_update' => 'datetime',
            'ameex_raw_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class);
    }
}
