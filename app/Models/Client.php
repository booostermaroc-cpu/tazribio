<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'logo',
        'phone',
        'second_phone',
        'city',
        'address',
        'notes',
        'is_blacklisted',
    ];

    protected function casts(): array
    {
        return [
            'is_blacklisted' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }

    public function blacklistHistories(): HasMany
    {
        return $this->hasMany(BlacklistHistory::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (blank($this->logo)) {
                return null;
            }

            if (Storage::disk('public')->exists($this->logo)) {
                return Storage::disk('public')->url($this->logo);
            }

            if (Storage::disk('local')->exists($this->logo)) {
                return Storage::disk('local')->url($this->logo);
            }

            return asset('storage/'.$this->logo);
        });
    }
}
