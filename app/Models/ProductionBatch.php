<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'warehouse_id',
        'status',
        'production_date',
        'created_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'production_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'released' => 'Released',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(ProductionBatchProduct::class, 'production_batch_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(BatchMaterial::class, 'production_batch_id');
    }

    public function additions(): HasMany
    {
        return $this->hasMany(BatchMaterialAddition::class, 'production_batch_id');
    }

    public function defects(): HasMany
    {
        return $this->hasMany(BatchDefect::class, 'production_batch_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(BatchOutput::class, 'production_batch_id');
    }

    public function getYieldAttribute(): ?float
    {
        $planned = $this->products->sum('planned_qty');
        $good = $this->products->sum('good_qty');
        if ($planned <= 0) {
            return null;
        }
        return round($good / $planned * 100, 1);
    }

    public function getDefectRateAttribute(): ?float
    {
        $good = $this->products->sum('good_qty');
        $defect = $this->products->sum('defect_qty');
        $total = $good + $defect;
        if ($total <= 0) {
            return null;
        }
        return round($defect / $total * 100, 1);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function canBeReleased(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeStarted(): bool
    {
        return $this->status === 'released';
    }

    public function canRecordOutput(): bool
    {
        return $this->status === 'in_progress';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'in_progress';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'released', 'in_progress']);
    }
}
