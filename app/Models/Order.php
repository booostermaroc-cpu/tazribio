<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AgentCommission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'client_id',
        'total_amount',
        'delivery_fee',
        'carrier_fee_amount',
        'carrier_fee_rule_key',
        'discount',
        'final_amount',
        'profit_amount',
        'profit_is_manual',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_receiver_name',
        'payment_receiver_rib',
        'payment_receipt',
        'payment_received_at',
        'payment_notes',
        'source',
        'city',
        'address',
        'notes',
        'created_by',
        'confirmed_by',
        'stock_deducted',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'carrier_fee_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'profit_amount' => 'decimal:2',
            'profit_is_manual' => 'boolean',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_received_at' => 'datetime',
            'source' => OrderSource::class,
            'stock_deducted' => 'boolean',
        ];
    }

    /** @param  Builder<Order>  $query */
    public function scopeExcludingCod(Builder $query): Builder
    {
        return $query->where('payment_method', '!=', PaymentMethod::Cod->value);
    }

    public function isCod(): bool
    {
        return $this->payment_method === PaymentMethod::Cod;
    }

    /** Montant COD envoyé au transporteur (prix produit réel, sans commission commande). */
    public function carrierCodAmount(): float
    {
        return round(max(0, (float) $this->total_amount - (float) $this->discount), 2);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AgentCommission::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function trackingHistories(): HasMany
    {
        return $this->hasMany(OrderTrackingHistory::class);
    }

    public function confirmationLogs(): HasMany
    {
        return $this->hasMany(OrderConfirmationLog::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function returnBons(): HasMany
    {
        return $this->hasMany(ReturnBon::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(OrderReview::class);
    }
}
