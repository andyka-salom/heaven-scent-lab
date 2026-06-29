<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialStock extends Model
{
    protected $fillable = [
        'warehouse_id',
        'material_id',
        'quantity',
        'min_alert',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'min_alert' => 'decimal:3',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function isLowStock(): bool
    {
        return $this->min_alert > 0 && $this->quantity <= $this->min_alert;
    }
}
