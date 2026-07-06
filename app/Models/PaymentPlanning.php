<?php

namespace App\Models;

use App\Enums\PaymentPlanningStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanning extends Model
{
    protected $fillable = [
        'delivery_company_id',
        'total_amount',
        'expected_payment_date',
        'status',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'expected_payment_date' => 'date',
            'status' => PaymentPlanningStatus::class,
            'received_at' => 'datetime',
        ];
    }

    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }
}
