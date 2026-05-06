<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Models\Material;
use App\Bundles\Warehouse\Models\Slab;
use App\Bundles\Warehouse\Models\StockMovement;
use App\Bundles\Warehouse\Utils\SlabStatus;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Slabs extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $material_id = null;
    public string $code = '';
    public ?int $length_cm = null;
    public ?int $width_cm = null;
    public ?int $thickness_cm = null;
    public string $status = 'available';
    public string $location = '';
    public string $barcode = '';
    public string $source = '';
    public string $supplier = '';
    public string $received_at = '';
    public string $shipped_at = '';
    public string $notes = '';
    public string $search = '';
    public string $statusFilter = '';
    public string $materialFilter = '';
    public bool $showModal = false;
    public bool $showDetailsModal = false;
    public ?int $detailsSlabId = null;

    public function create(): void
    {
        $this->authorizeWarehouseManager();

        $this->resetForm();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeWarehouseManager();

        $data = $this->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'code' => ['required', 'string', 'max:80', Rule::unique('slabs', 'code')->ignore($this->editingId)],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('slabs', 'barcode')->ignore($this->editingId)],
            'length_cm' => ['required', 'integer', 'min:1'],
            'width_cm' => ['required', 'integer', 'min:1'],
            'thickness_cm' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(SlabStatus::class)],
            'location' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:120'],
            'supplier' => ['nullable', 'string', 'max:120'],
            'received_at' => ['nullable', 'date'],
            'shipped_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $data['barcode'] = $data['barcode'] ?: null;
        $data['received_at'] = $data['received_at'] ?: null;
        $data['shipped_at'] = $data['shipped_at'] ?: null;

        $slab = Slab::firstOrNew(['id' => $this->editingId]);
        $isNew = ! $slab->exists;

        if ($isNew) {
            $slab->created_by_id = auth()->id();
        }

        $slab->fill($data);
        $changes = $this->changesFor($slab, [
            'material_id' => 'material',
            'code' => 'code',
            'length_cm' => 'length',
            'width_cm' => 'width',
            'thickness_cm' => 'thickness',
            'status' => 'status',
            'location' => 'location',
            'barcode' => 'barcode',
            'source' => 'source',
            'supplier' => 'supplier',
            'received_at' => 'received at',
            'shipped_at' => 'shipped at',
            'notes' => 'notes',
        ]);

        $slab->save();

        if ($isNew) {
            if (! $slab->barcode) {
                $slab->update(['barcode' => $this->barcodeFor($slab)]);
            }

            $this->logMovement('arrived', $slab, "Received item {$slab->code} into warehouse.");
        } elseif ($changes) {
            $this->logMovement('updated', $slab, "Updated item {$slab->code}.", $changes);
        }

        $this->resetForm();
        $this->showModal = false;
        $this->dispatch('notify', message: 'Slab saved.');
    }

    public function edit(Slab $slab): void
    {
        $this->authorizeWarehouseManager();

        $this->editingId = $slab->id;
        $this->material_id = $slab->material_id;
        $this->code = $slab->code;
        $this->length_cm = $slab->length_cm;
        $this->width_cm = $slab->width_cm;
        $this->thickness_cm = $slab->thickness_cm;
        $this->status = $slab->status->value;
        $this->location = (string) $slab->location;
        $this->barcode = (string) $slab->barcode;
        $this->source = (string) $slab->source;
        $this->supplier = (string) $slab->supplier;
        $this->received_at = $slab->received_at?->format('Y-m-d\TH:i') ?? '';
        $this->shipped_at = $slab->shipped_at?->format('Y-m-d\TH:i') ?? '';
        $this->notes = (string) $slab->notes;
        $this->showModal = true;
    }

    public function details(Slab $slab): void
    {
        $this->detailsSlabId = $slab->id;
        $this->showDetailsModal = true;
    }

    public function delete(Slab $slab): void
    {
        $this->authorizeWarehouseManager();

        $this->logMovement('archived', $slab, "Archived item {$slab->code}.");
        $slab->delete();
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function closeDetailsModal(): void
    {
        $this->reset(['detailsSlabId']);
        $this->showDetailsModal = false;
    }

    public function exportCsv()
    {
        return response()->streamDownload(function (): void {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Code',
                'Barcode',
                'Material',
                'Status',
                'Location',
                'Length cm',
                'Width cm',
                'Thickness cm',
                'Area m2',
                'Source',
                'Supplier',
                'Received at',
                'Shipped at',
            ]);

            $this->slabsQuery()
                ->with('material')
                ->orderBy('code')
                ->get()
                ->each(function (Slab $slab) use ($file): void {
                    fputcsv($file, [
                        $slab->code,
                        $slab->barcode_value,
                        $slab->material->name,
                        $slab->status->label(),
                        $slab->location,
                        $slab->length_cm,
                        $slab->width_cm,
                        $slab->thickness_cm,
                        number_format($slab->area_m2, 2, '.', ''),
                        $slab->source,
                        $slab->supplier,
                        $slab->received_at?->format('Y-m-d H:i:s'),
                        $slab->shipped_at?->format('Y-m-d H:i:s'),
                    ]);
                });

            fclose($file);
        }, 'warehouse-items.csv', ['Content-Type' => 'text/csv']);
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'material_id',
            'code',
            'length_cm',
            'width_cm',
            'thickness_cm',
            'location',
            'barcode',
            'source',
            'supplier',
            'received_at',
            'shipped_at',
            'notes',
        ]);

        $this->status = SlabStatus::Available->value;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMaterialFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'materialFilter']);
        $this->resetPage();
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

    private function barcodeFor(Slab $slab): string
    {
        return 'WH-'.str_pad((string) $slab->id, 6, '0', STR_PAD_LEFT);
    }

    private function slabsQuery()
    {
        return Slab::query()
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('code', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%")
                        ->orWhere('location', 'like', "%{$this->search}%")
                        ->orWhereHas('material', fn ($material) => $material->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->materialFilter, fn ($query) => $query->where('material_id', $this->materialFilter));
    }

    private function changesFor(Slab $slab, array $labels): array
    {
        return collect($slab->getDirty())
            ->except(['created_by_id', 'created_at', 'updated_at'])
            ->mapWithKeys(fn ($value, string $field) => [
                $labels[$field] ?? $field => [
                    'from' => $this->displayValue($field, $slab->getOriginal($field)),
                    'to' => $this->displayValue($field, $value),
                ],
            ])
            ->all();
    }

    private function displayValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if ($field === 'material_id') {
            return Material::find($value)?->name ?? '-';
        }

        if ($field === 'status') {
            $status = $value instanceof SlabStatus ? $value : SlabStatus::tryFrom((string) $value);

            return $status?->label() ?? (string) $value;
        }

        return (string) $value;
    }

    public function render()
    {
        $statusCounts = Slab::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('Warehouse::Livewire.slabs', [
            'materials' => Material::active()->orderBy('name')->get(),
            'statuses' => SlabStatus::options(),
            'statusCounts' => $statusCounts,
            'slabs' => $this->slabsQuery()
                ->with('material')
                ->latest()
                ->paginate(12),
            'detailsSlab' => $this->detailsSlabId
                ? Slab::with(['material', 'creator'])->find($this->detailsSlabId)
                : null,
            'detailsMovements' => $this->detailsSlabId
                ? StockMovement::query()
                    ->with('actor')
                    ->where('type', 'item')
                    ->where('subject_id', $this->detailsSlabId)
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
        ]);
    }
}
