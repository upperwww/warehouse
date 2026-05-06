<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Models\Material;
use App\Bundles\Warehouse\Models\Slab;
use App\Bundles\Warehouse\Models\StockMovement;
use App\Bundles\Warehouse\Utils\SlabStatus;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ItemFlow extends Component
{
    public string $mode = 'receive';
    public ?int $material_id = null;
    public ?int $slab_id = null;
    public string $code = '';
    public string $barcode = '';
    public ?int $length_cm = null;
    public ?int $width_cm = null;
    public ?int $thickness_cm = null;
    public string $location = '';
    public string $source = '';
    public string $supplier = '';
    public string $received_at = '';
    public string $shipped_at = '';
    public string $notes = '';

    public function showReceive(): void
    {
        $this->mode = 'receive';
        $this->resetForm();
    }

    public function showShip(): void
    {
        $this->mode = 'ship';
        $this->resetForm();
    }

    public function receive(): void
    {
        $this->authorizeWarehouseManager();

        $data = $this->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'code' => ['required', 'string', 'max:80', 'unique:slabs,code'],
            'barcode' => ['nullable', 'string', 'max:80', 'unique:slabs,barcode'],
            'length_cm' => ['required', 'integer', 'min:1'],
            'width_cm' => ['required', 'integer', 'min:1'],
            'thickness_cm' => ['required', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:120'],
            'supplier' => ['nullable', 'string', 'max:120'],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $slab = DB::transaction(function () use ($data): Slab {
            $slab = Slab::create([
                ...$data,
                'barcode' => $data['barcode'] ?: null,
                'created_by_id' => auth()->id(),
                'status' => SlabStatus::Available,
                'received_at' => $data['received_at'] ?: now(),
            ]);

            if (! $slab->barcode) {
                $slab->update(['barcode' => $this->barcodeFor($slab)]);
            }

            $this->logMovement('arrived', $slab, "Received item {$slab->code} into warehouse.", [
                'source' => ['from' => '-', 'to' => $slab->source ?: '-'],
                'supplier' => ['from' => '-', 'to' => $slab->supplier ?: '-'],
                'location' => ['from' => '-', 'to' => $slab->location ?: '-'],
            ]);

            return $slab;
        });

        $this->resetForm();
        $this->dispatch('notify', message: "Item {$slab->code} received.");
    }

    public function ship(): void
    {
        $this->authorizeWarehouseManager();

        $data = $this->validate([
            'slab_id' => ['required', 'exists:slabs,id'],
            'shipped_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $slab = Slab::findOrFail($data['slab_id']);

        $slab->update([
            'status' => SlabStatus::Sold,
            'shipped_at' => $data['shipped_at'] ?: now(),
            'notes' => $data['notes'] ?: $slab->notes,
        ]);

        $this->logMovement('shipped', $slab, "Shipped item {$slab->code} from warehouse.", [
            'status' => ['from' => 'Available', 'to' => 'Sold'],
            'shipped at' => ['from' => '-', 'to' => $slab->shipped_at?->format('d.m.Y H:i') ?? '-'],
        ]);

        $this->resetForm();
        $this->dispatch('notify', message: "Item {$slab->code} shipped.");
    }

    public function resetForm(): void
    {
        $this->reset([
            'material_id',
            'slab_id',
            'code',
            'barcode',
            'length_cm',
            'width_cm',
            'thickness_cm',
            'location',
            'source',
            'supplier',
            'received_at',
            'shipped_at',
            'notes',
        ]);
    }

    private function barcodeFor(Slab $slab): string
    {
        return 'WH-'.str_pad((string) $slab->id, 6, '0', STR_PAD_LEFT);
    }

    private function authorizeWarehouseManager(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Manager']), 403);
    }

    private function logMovement(string $action, Slab $slab, string $description, ?array $changes = null): void
    {
        StockMovement::create([
            'type' => 'item',
            'action' => $action,
            'subject_id' => $slab->id,
            'subject_name' => $slab->code,
            'actor_id' => auth()->id(),
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    public function render()
    {
        return view('Warehouse::Livewire.item-flow', [
            'materials' => Material::active()->orderBy('name')->get(),
            'availableSlabs' => Slab::query()
                ->with('material')
                ->whereIn('status', [SlabStatus::Available->value, SlabStatus::Reserved->value])
                ->orderBy('code')
                ->get(),
        ]);
    }
}
