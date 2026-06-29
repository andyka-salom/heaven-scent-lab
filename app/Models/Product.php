<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'item_name',
        'variant_name',
        'full_name',
        'unit',
        'default_warehouse_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->full_name = $product->item_name . ' / ' . $product->variant_name;
        });

        static::updating(function (Product $product) {
            if ($product->isDirty('item_name') || $product->isDirty('variant_name')) {
                $product->full_name = $product->item_name . ' / ' . $product->variant_name;
            }
        });
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function boms(): HasMany
    {
        return $this->hasMany(Bom::class);
    }

    public function activeBom(): HasOne
    {
        return $this->hasOne(Bom::class)->where('is_active', true);
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
