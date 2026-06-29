<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchMaterialAddition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'production_batch_id',
        'product_id',
        'material_id',
        'type',
        'quantity',
        'reason',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
