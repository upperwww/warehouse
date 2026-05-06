<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Models\StockMovement;
use App\Bundles\Warehouse\Utils\UserRole;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class StockMovements extends Component
{
    use WithPagination;

    public string $roleFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $actionFilter = '';
    public string $search = '';
    public string $movementType = 'item';
    public ?int $selectedMovementId = null;

    public function showItems(): void
    {
        $this->movementType = 'item';
        $this->actionFilter = '';
        $this->resetMovementPages();
    }

    public function showMaterials(): void
    {
        $this->movementType = 'material';
        $this->actionFilter = '';
        $this->resetMovementPages();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetMovementPages();
    }

    public function updatingDateFrom(): void
    {
        $this->resetMovementPages();
    }

    public function updatingDateTo(): void
    {
        $this->resetMovementPages();
    }

    public function updatingActionFilter(): void
    {
        $this->resetMovementPages();
    }

    public function updatingSearch(): void
    {
        $this->resetMovementPages();
    }

    public function clearFilters(): void
    {
        $this->reset(['roleFilter', 'dateFrom', 'dateTo', 'actionFilter', 'search']);
        $this->resetMovementPages();
    }

    public function showDetails(int $movementId): void
    {
        $this->selectedMovementId = $movementId;
    }

    public function closeDetails(): void
    {
        $this->selectedMovementId = null;
    }

    private function resetMovementPages(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('Warehouse::Livewire.stock-movements', [
            'roles' => UserRole::options(),
            'movementType' => $this->movementType,
            'actions' => StockMovement::query()
                ->where('type', $this->movementType)
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'movements' => StockMovement::query()
                ->with(['actor.roles'])
                ->where('type', $this->movementType)
                ->when($this->search, function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('subject_name', 'like', "%{$this->search}%")
                            ->orWhere('description', 'like', "%{$this->search}%");
                    });
                })
                ->when($this->actionFilter, fn ($query) => $query->where('action', $this->actionFilter))
                ->when($this->roleFilter, function ($query): void {
                    $query->whereHas('actor', fn ($actor) => $actor->role($this->roleFilter));
                })
                ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
                ->latest()
                ->paginate(12),
            'selectedMovement' => $this->selectedMovementId
                ? StockMovement::with(['actor.roles'])->find($this->selectedMovementId)
                : null,
        ]);
    }
}
