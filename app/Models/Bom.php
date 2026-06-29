<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    protected $fillable = [
        'product_id',
        'version',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function totalMaterials(): int
    {
        return $this->items()->count();
    }
}
