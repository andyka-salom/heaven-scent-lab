<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchOutput extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'production_batch_id',
        'product_id',
        'good_qty',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'good_qty' => 'integer',
        'created_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
