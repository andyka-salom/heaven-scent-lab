<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchDefect extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'production_batch_id',
        'product_id',
        'defect_qty',
        'reason',
        'notes',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'defect_qty' => 'integer',
        'created_at' => 'datetime',
    ];

    public const REASONS = [
        'bottle_broken' => 'Botol Pecah',
        'spray_fault' => 'Spray Rusak',
        'contamination' => 'Kontaminasi',
        'color_off' => 'Warna Tidak Sesuai',
        'leak' => 'Bocor',
        'other' => 'Lainnya',
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

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
