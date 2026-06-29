<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'code',
        'name',
        'location',
        'is_active',
        'allow_negative_stock',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_negative_stock' => 'boolean',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(MaterialStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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
