<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'logo',
        'phone',
        'address',
        'rib',
        'default_delivery_fee',
        'carrier_fee_rules',
        'invoice_prefix',
        'order_prefix',
        'default_payment_method',
        'default_delivery_company_id',
        'return_bon_prefix',
        'agent_commission_default_type',
        'agent_commission_default_value',
        'agent_commission_apply_on',
        'profit_include_delivery_fee',
        'use_manual_profit_total',
        'manual_profit_total',
        'carrier_stuck_days',
    ];

    protected function casts(): array
    {
        return [
            'default_delivery_fee' => 'decimal:2',
            'carrier_fee_rules' => 'array',
            'agent_commission_default_value' => 'decimal:2',
            'profit_include_delivery_fee' => 'boolean',
            'use_manual_profit_total' => 'boolean',
            'manual_profit_total' => 'decimal:2',
            'carrier_stuck_days' => 'integer',
        ];
    }

    public function defaultDeliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class, 'default_delivery_company_id');
    }
}
