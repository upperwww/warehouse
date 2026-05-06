<?php

namespace App\Bundles\Warehouse\Models;

use App\Bundles\Warehouse\Utils\SlabStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slab extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'material_id',
        'created_by_id',
        'code',
        'barcode',
        'length_cm',
        'width_cm',
        'thickness_cm',
        'status',
        'location',
        'source',
        'supplier',
        'received_at',
        'shipped_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'length_cm' => 'integer',
            'width_cm' => 'integer',
            'thickness_cm' => 'integer',
            'status' => SlabStatus::class,
            'received_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'subject_id')
            ->where('type', 'item');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryCheckItem::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', SlabStatus::Available);
    }

    public function getAreaM2Attribute(): float
    {
        return round(($this->length_cm * $this->width_cm) / 10000, 2);
    }

    public function getBarcodeValueAttribute(): string
    {
        return $this->barcode ?: 'WH-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
