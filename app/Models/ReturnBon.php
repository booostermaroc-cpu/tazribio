<?php

namespace App\Models;

use App\Enums\ReturnBonStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnBon extends Model
{
    protected $fillable = [
        'order_id',
        'return_number',
        'barcode_token',
        'reason',
        'with_packaging',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReturnBonStatus::class,
            'with_packaging' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
