<?php

namespace App\Bundles\Warehouse\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCheckItem extends Model
{
    protected $fillable = [
        'inventory_check_id',
        'slab_id',
        'expected_status',
        'expected_location',
        'actual_status',
        'actual_location',
        'result',
        'checked_by_id',
        'checked_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function inventoryCheck(): BelongsTo
    {
        return $this->belongsTo(InventoryCheck::class);
    }

    public function slab(): BelongsTo
    {
        return $this->belongsTo(Slab::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_id');
    }
}
