<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    protected $fillable = [
        'shipment_id',
        'status',
        'description',
        'event_date',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
