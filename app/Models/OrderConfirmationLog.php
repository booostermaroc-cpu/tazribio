<?php

namespace App\Models;

use App\Enums\OrderConfirmationAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderConfirmationLog extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'action',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action' => OrderConfirmationAction::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
