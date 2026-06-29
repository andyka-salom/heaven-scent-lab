<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionBatchProduct extends Model
{
    protected $fillable = [
        'production_batch_id',
        'product_id',
        'planned_qty',
        'good_qty',
        'defect_qty',
    ];

    protected $casts = [
        'planned_qty' => 'integer',
        'good_qty' => 'integer',
        'defect_qty' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getYieldAttribute(): ?float
    {
        if ($this->planned_qty <= 0) {
            return null;
        }
        return round($this->good_qty / $this->planned_qty * 100, 1);
    }

    public function getDefectRateAttribute(): ?float
    {
        $total = $this->good_qty + $this->defect_qty;
        if ($total <= 0) {
            return null;
        }
        return round($this->defect_qty / $total * 100, 1);
    }
}
