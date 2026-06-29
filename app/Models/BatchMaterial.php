<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchMaterial extends Model
{
    protected $fillable = [
        'production_batch_id',
        'material_id',
        'planned_qty',
        'issued_qty',
        'unit',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:3',
        'issued_qty' => 'decimal:3',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
