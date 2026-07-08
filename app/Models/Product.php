<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'ameex_reference',
        'image',
        'purchase_price',
        'selling_price',
        'current_stock',
        'stock_alert',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'current_stock' => 'integer',
            'stock_alert' => 'integer',
            'status' => ProductStatus::class,
        ];
    }

    public function warehouseProducts(): HasMany
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->stock_alert;
    }

    /**
     * External reference sent to Ameex in STOCK mode (products[n][ref]).
     * Priority: ameex_reference → sku. Never uses product_id.
     */
    public function ameexStockReference(): ?string
    {
        if (filled(trim((string) $this->ameex_reference))) {
            return trim((string) $this->ameex_reference);
        }

        $sku = trim((string) $this->sku);

        return filled($sku) ? $sku : null;
    }

    public function usesAmeexReferenceOverride(): bool
    {
        return filled(trim((string) $this->ameex_reference));
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (blank($this->image)) {
                return null;
            }

            if (Storage::disk('public')->exists($this->image)) {
                return Storage::disk('public')->url($this->image);
            }

            if (Storage::disk('local')->exists($this->image)) {
                return Storage::disk('local')->url($this->image);
            }

            return asset('storage/'.$this->image);
        });
    }
}
