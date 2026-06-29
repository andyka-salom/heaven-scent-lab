<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPES = [
        'oil' => 'Oil',
        'alcohol' => 'Alcohol',
        'bottle' => 'Botol',
        'cap' => 'Tutup',
        'spray' => 'Spray',
        'atomizer' => 'Atomizer',
        'box' => 'Box',
        'paperbag' => 'Paperbag',
        'card' => 'Card',
        'other' => 'Lainnya',
    ];

    public const UNITS = ['ml', 'pcs'];

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(MaterialStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
