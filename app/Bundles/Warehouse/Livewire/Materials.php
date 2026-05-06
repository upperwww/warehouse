<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Models\Material;
use App\Bundles\Warehouse\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Materials extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public string $existingImagePath = '';
    public bool $is_active = true;
    public string $search = '';
    public bool $showModal = false;
    public bool $showReplacementModal = false;
    public ?int $deletingMaterialId = null;
    public ?int $replacementMaterialId = null;
    public int $replacementSlabCount = 0;
    public $photo = null;

    public function create(): void
    {
        $this->authorizeWarehouseWrite();

        $this->resetForm();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeWarehouseWrite();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($data['photo']);

        if ($this->photo) {
            $this->deleteImage($this->existingImagePath);
            $data['image_path'] = $this->photo->store('materials', 'public');
        }

        $material = Material::firstOrNew(['id' => $this->editingId]);
        $isNew = ! $material->exists;

        if ($isNew) {
            $material->created_by_id = auth()->id();
        }

        $material->fill($data);
        $changes = $this->changesFor($material, [
            'name' => 'name',
            'description' => 'description',
            'image_path' => 'photo',
            'is_active' => 'status',
        ]);

        $material->save();

        if ($isNew) {
            $this->logMovement('created', $material, "Created material {$material->name}.");
        } elseif ($changes) {
            $this->logMovement('updated', $material, "Updated material {$material->name}.", $changes);
        }

        $this->resetForm();
        $this->showModal = false;
        $this->dispatch('notify', message: 'Material saved.');
    }

    public function edit(Material $material): void
    {
        $this->authorizeWarehouseWrite();

        $this->editingId = $material->id;
        $this->name = $material->name;
        $this->description = (string) $material->description;
        $this->is_active = $material->is_active;
        $this->existingImagePath = (string) $material->image_path;
        $this->photo = null;
        $this->showModal = true;
    }

    public function delete(Material $material): void
    {
        $this->authorizeWarehouseWrite();

        $slabCount = $material->slabs()->count();

        if ($slabCount === 0) {
            $this->deleteMaterial($material);
            return;
        }

        $replacement = Material::query()
            ->whereKeyNot($material->id)
            ->orderBy('name')
            ->first();

        if (! $replacement) {
            $this->dispatch('notify', message: 'Create another material before deleting this one.');
            return;
        }

        $this->deletingMaterialId = $material->id;
        $this->replacementMaterialId = $replacement->id;
        $this->replacementSlabCount = $slabCount;
        $this->showReplacementModal = true;
    }

    public function replaceAndDelete(): void
    {
        $this->authorizeWarehouseWrite();

        $data = $this->validate([
            'deletingMaterialId' => ['required', 'exists:materials,id'],
            'replacementMaterialId' => [
                'required',
                'exists:materials,id',
                Rule::notIn([$this->deletingMaterialId]),
            ],
        ]);

        $material = Material::findOrFail($data['deletingMaterialId']);
        $replacement = Material::findOrFail($data['replacementMaterialId']);
        $slabCount = $material->slabs()->count();

        DB::transaction(function () use ($material, $replacement, $slabCount): void {
            $material->slabs()->update(['material_id' => $replacement->id]);

            $this->logMovement(
                'archived',
                $material,
                "Archived material {$material->name} and moved {$slabCount} item(s) to {$replacement->name}.",
                [
                    'replacement material' => [
                        'from' => $material->name,
                        'to' => $replacement->name,
                    ],
                    'moved items' => [
                        'from' => (string) $slabCount,
                        'to' => (string) $slabCount,
                    ],
                ],
            );

            $material->delete();
        });

        $this->closeReplacementModal();
        $this->resetForm();
        $this->dispatch('notify', message: 'Material archived and items moved.');
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function closeReplacementModal(): void
    {
        $this->reset(['deletingMaterialId', 'replacementMaterialId', 'replacementSlabCount']);
        $this->showReplacementModal = false;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'existingImagePath', 'photo']);
        $this->is_active = true;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function authorizeWarehouseWrite(): void
    {
        abort_unless(auth()->user()?->canManageWarehouse(), 403);
    }

    private function deleteMaterial(Material $material): void
    {
        $this->logMovement('archived', $material, "Archived material {$material->name}.");
        $material->delete();
        $this->resetForm();
        $this->dispatch('notify', message: 'Material archived.');
    }

    private function logMovement(string $action, Material $material, string $description, ?array $changes = null): void
    {
        StockMovement::create([
            'type' => 'material',
            'action' => $action,
            'subject_id' => $material->id,
            'subject_name' => $material->name,
            'actor_id' => auth()->id(),
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    private function changesFor(Material $material, array $labels): array
    {
        return collect($material->getDirty())
            ->except(['created_by_id', 'created_at', 'updated_at'])
            ->mapWithKeys(fn ($value, string $field) => [
                $labels[$field] ?? $field => [
                    'from' => $this->displayValue($field, $material->getOriginal($field)),
                    'to' => $this->displayValue($field, $value),
                ],
            ])
            ->all();
    }

    private function displayValue(string $field, mixed $value): string
    {
        if ($field === 'image_path' && $value) {
            return 'photo uploaded';
        }

        if ($field === 'is_active') {
            return $value ? 'Active' : 'Inactive';
        }

        if ($value === null || $value === '') {
            return '-';
        }

        return (string) $value;
    }

    public function render()
    {
        return view('Warehouse::Livewire.materials', [
            'materials' => Material::query()
                ->withCount('slabs')
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),
            'replacementMaterials' => Material::query()
                ->when($this->deletingMaterialId, fn ($query) => $query->whereKeyNot($this->deletingMaterialId))
                ->orderBy('name')
                ->get(),
            'deletingMaterial' => $this->deletingMaterialId ? Material::find($this->deletingMaterialId) : null,
        ]);
    }
}
