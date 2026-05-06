<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Models\InventoryCheck;
use App\Bundles\Warehouse\Models\Material;
use App\Bundles\Warehouse\Models\Slab;
use App\Bundles\Warehouse\Models\StockMovement;
use App\Bundles\Warehouse\Utils\SlabStatus;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $canViewActivity = auth()->user()?->hasAnyRole(['Admin', 'Manager']) ?? false;

        $statusCounts = Slab::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('Warehouse::Livewire.dashboard', [
            'materialsCount' => Material::count(),
            'slabsCount' => Slab::count(),
            'availableCount' => $statusCounts[SlabStatus::Available->value] ?? 0,
            'reservedCount' => $statusCounts[SlabStatus::Reserved->value] ?? 0,
            'damagedCount' => $statusCounts[SlabStatus::Damaged->value] ?? 0,
            'missingCount' => $statusCounts[SlabStatus::Missing->value] ?? 0,
            'totalArea' => Slab::all()->sum('area_m2'),
            'recentSlabs' => Slab::with('material')->latest()->take(6)->get(),
            'problemSlabs' => Slab::with('material')
                ->whereIn('status', [SlabStatus::Damaged->value, SlabStatus::Missing->value])
                ->latest()
                ->take(6)
                ->get(),
            'latestInventory' => InventoryCheck::query()
                ->withCount('items')
                ->latest()
                ->first(),
            'canViewActivity' => $canViewActivity,
            'recentFlow' => $canViewActivity
                ? StockMovement::query()
                    ->with('actor')
                    ->where('type', 'item')
                    ->whereIn('action', ['arrived', 'shipped', 'inventory'])
                    ->latest()
                    ->take(6)
                    ->get()
                : collect(),
            'statuses' => SlabStatus::cases(),
            'statusCounts' => $statusCounts,
        ]);
    }
}
