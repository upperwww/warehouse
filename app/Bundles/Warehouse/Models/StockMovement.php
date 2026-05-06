<?php

namespace App\Bundles\Warehouse\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'type',
        'action',
        'subject_id',
        'subject_name',
        'actor_id',
        'description',
        'changes',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
