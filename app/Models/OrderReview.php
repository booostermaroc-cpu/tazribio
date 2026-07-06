<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReview extends Model
{
    protected $fillable = [
        'order_id',
        'token',
        'product_rating',
        'service_rating',
        'comment',
        'submitted_at',
        'link_sent_at',
        'link_sent_by',
    ];

    protected function casts(): array
    {
        return [
            'product_rating' => 'integer',
            'service_rating' => 'integer',
            'submitted_at' => 'datetime',
            'link_sent_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function linkSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'link_sent_by');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }
}
