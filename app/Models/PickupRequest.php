<?php

namespace App\Models;

use App\Enums\PickupRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupRequest extends Model
{
    protected $fillable = [
        'delivery_company_id',
        'pickup_address',
        'pickup_phone',
        'ameex_city_id',
        'requested_date',
        'status',
        'notes',
        'ameex_request_ref',
        'ameex_status',
        'ameex_raw_response',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'status' => PickupRequestStatus::class,
            'ameex_raw_response' => 'array',
        ];
    }

    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }
}
