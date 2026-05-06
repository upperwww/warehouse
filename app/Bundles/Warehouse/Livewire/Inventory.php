<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Models\InventoryCheck;
use App\Bundles\Warehouse\Models\InventoryCheckItem;
use App\Bundles\Warehouse\Models\Slab;
use App\Bundles\Warehouse\Models\StockMovement;
use App\Bundles\Warehouse\Utils\SlabStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Inventory extends Component
{
    use WithPagination;

    public string $name = '';
    public ?int $activeCheckId = null;
    public string $scanBarcode = '';
    public string $actualLocation = '';
    public string $actualStatus = '';
    public string $note = '';
    public string $resultFilter = '';

    public function mount(): void
    {
        $this->activeCheckId = InventoryCheck::query()
            ->where('status', 'active')
            ->latest()
            ->value('id');
    }

    public function startInventory(): void
    {
        $data = $this->validate([
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $check = DB::transaction(function () use ($data): InventoryCheck {
            $check = InventoryCheck::create([
                'name' => $data['name'] ?: 'Inventory '.now()->format('d.m.Y H:i'),
                'status' => 'active',
                'started_by_id' => auth()->id(),
                'started_at' => now(),
            ]);

            Slab::query()
                ->whereIn('status', [
                    SlabStatus::Available->value,
                    SlabStatus::Reserved->value,
                    SlabStatus::Damaged->value,
                    SlabStatus::Missing->value,
                ])
                ->orderBy('code')
                ->get()
                ->each(function (Slab $slab) use ($check): void {
                    InventoryCheckItem::create([
                        'inventory_check_id' => $check->id,
                        'slab_id' => $slab->id,
                        'expected_status' => $this->expectedStatusFor($slab),
                        'expected_location' => $slab->location,
                    ]);
                });

            return $check;
        });

        $this->activeCheckId = $check->id;
        $this->name = '';
        $this->resetPage();
        $this->dispatch('notify', message: 'Inventory started.');
    }

    public function openCheck(int $checkId): void
    {
        $check = InventoryCheck::findOrFail($checkId);

        abort_unless($this->canManageInventory() || $check->status === 'active', 403);

        $this->activeCheckId = $checkId;
        $this->clearScanForm();
        $this->resetPage();
    }

    public function useBarcode(string $barcode): void
    {
        $this->scanBarcode = $barcode;
    }

    public function scan(): void
    {
        $check = $this->activeCheck();

        if (! $check || $check->status !== 'active') {
            $this->dispatch('notify', message: 'Start an active inventory first.');
            return;
        }

        $data = $this->validate([
            'scanBarcode' => ['required', 'string', 'max:120'],
            'actualLocation' => ['nullable', 'string', 'max:120'],
            'actualStatus' => ['nullable', Rule::enum(SlabStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $slab = Slab::query()
            ->where('barcode', $data['scanBarcode'])
            ->orWhere('code', $data['scanBarcode'])
            ->first();

        if (! $slab) {
            $this->addError('scanBarcode', 'No item found for this barcode.');
            return;
        }

        $item = InventoryCheckItem::query()
            ->where('inventory_check_id', $check->id)
            ->where('slab_id', $slab->id)
            ->first();

        if (! $item) {
            $this->addError('scanBarcode', 'This item is not part of the selected inventory.');
            return;
        }

        $actualLocation = $data['actualLocation'] !== '' ? $data['actualLocation'] : $slab->location;
        $actualStatus = $data['actualStatus']
            ? SlabStatus::from($data['actualStatus'])
            : SlabStatus::from($item->expected_status);

        $item->update([
            'actual_location' => $actualLocation,
            'actual_status' => $actualStatus->value,
            'result' => $this->resultFor($item, $actualLocation, $actualStatus),
            'checked_by_id' => auth()->id(),
            'checked_at' => now(),
            'note' => $data['note'] ?: null,
        ]);

        $this->clearScanForm();
        $this->resetPage();
        $this->dispatch('notify', message: "Item {$slab->code} checked.");
    }

    public function completeInventory(): void
    {
        $check = $this->activeCheck();

        if (! $check || $check->status !== 'active') {
            return;
        }

        DB::transaction(function () use ($check): void {
            $check->items()
                ->whereNull('checked_at')
                ->update([
                    'actual_status' => SlabStatus::Missing->value,
                    'result' => 'missing',
                    'checked_at' => now(),
                ]);

            $check->items()
                ->with('slab')
                ->get()
                ->each(function (InventoryCheckItem $item): void {
                    $slab = $item->slab;
                    $oldStatus = $slab->status;
                    $oldLocation = (string) $slab->location;
                    $updates = [
                        'location' => $item->actual_location ?: $item->expected_location,
                    ];

                    if ($item->result === 'missing') {
                        $updates['status'] = SlabStatus::Missing->value;
                    }

                    if ($oldStatus === SlabStatus::Missing && $item->result === 'found' && $item->actual_status) {
                        $updates['status'] = $item->actual_status;
                    }

                    if (in_array($item->result, ['damaged', 'wrong_status'], true) && $item->actual_status) {
                        $updates['status'] = $item->actual_status;
                    }

                    $slab->update($updates);
                    $this->logInventoryMovement($item, $slab, $oldStatus, $oldLocation);
                });

            $check->update([
                'status' => 'completed',
                'completed_by_id' => auth()->id(),
                'completed_at' => now(),
            ]);
        });

        $this->dispatch('notify', message: 'Inventory completed.');
    }

    public function clearScanForm(): void
    {
        $this->reset(['scanBarcode', 'actualLocation', 'note']);
        $this->actualStatus = '';
    }

    public function exportCsv()
    {
        $check = $this->activeCheck();

        if (! $check) {
            $this->dispatch('notify', message: 'Open an inventory first.');
            return null;
        }

        return response()->streamDownload(function () use ($check): void {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Inventory',
                'Item',
                'Barcode',
                'Material',
                'Expected status',
                'Expected location',
                'Actual status',
                'Actual location',
                'Result',
                'Checked by',
                'Checked at',
                'Note',
            ]);

            $check->items()
                ->with(['slab.material', 'checker'])
                ->orderBy('id')
                ->get()
                ->each(function (InventoryCheckItem $item) use ($file, $check): void {
                    fputcsv($file, [
                        $check->name,
                        $item->slab->code,
                        $item->slab->barcode_value,
                        $item->slab->material->name,
                        SlabStatus::tryFrom($item->expected_status)?->label() ?? $item->expected_status,
                        $item->expected_location,
                        $item->actual_status ? (SlabStatus::tryFrom($item->actual_status)?->label() ?? $item->actual_status) : '',
                        $item->actual_location,
                        $item->result ? str($item->result)->replace('_', ' ')->title() : 'Unchecked',
                        $item->checker?->name,
                        $item->checked_at?->format('Y-m-d H:i:s'),
                        $item->note,
                    ]);
                });

            fclose($file);
        }, 'inventory-'.$check->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function updatingResultFilter(): void
    {
        $this->resetPage();
    }

    private function resultFor(InventoryCheckItem $item, ?string $actualLocation, SlabStatus $actualStatus): string
    {
        if ($actualStatus === SlabStatus::Damaged) {
            return 'damaged';
        }

        if ($actualStatus->value !== $item->expected_status) {
            return 'wrong_status';
        }

        if ((string) $actualLocation !== (string) $item->expected_location) {
            return 'wrong_location';
        }

        return 'found';
    }

    private function expectedStatusFor(Slab $slab): string
    {
        if ($slab->status !== SlabStatus::Missing) {
            return $slab->status->value;
        }

        return InventoryCheckItem::query()
            ->where('slab_id', $slab->id)
            ->where('expected_status', '!=', SlabStatus::Missing->value)
            ->latest()
            ->value('expected_status') ?: SlabStatus::Available->value;
    }

    private function activeCheck(): ?InventoryCheck
    {
        return $this->activeCheckId
            ? InventoryCheck::with('items')->find($this->activeCheckId)
            : null;
    }

    private function authorizeWarehouseManager(): void
    {
        abort_unless($this->canManageInventory(), 403);
    }

    private function canManageInventory(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Manager']) ?? false;
    }

    private function logInventoryMovement(InventoryCheckItem $item, Slab $slab, SlabStatus $oldStatus, string $oldLocation): void
    {
        $changes = [];

        if ($oldStatus->value !== $slab->status->value) {
            $changes['status'] = [
                'from' => $oldStatus->label(),
                'to' => $slab->status->label(),
            ];
        }

        if ((string) $oldLocation !== (string) $slab->location) {
            $changes['location'] = [
                'from' => $oldLocation ?: '-',
                'to' => $slab->location ?: '-',
            ];
        }

        if ($changes === []) {
            return;
        }

        StockMovement::create([
            'type' => 'item',
            'action' => 'inventory',
            'subject_id' => $slab->id,
            'subject_name' => $slab->code,
            'actor_id' => auth()->id(),
            'description' => "Inventory marked {$slab->code} as ".str($item->result)->replace('_', ' ')->title().'.',
            'changes' => $changes,
        ]);
    }

    public function render()
    {
        $canManageInventory = $this->canManageInventory();
        $check = $this->activeCheck();
        $counts = $check
            ? $check->items()
                ->selectRaw("coalesce(result, 'unchecked') as result_name, count(*) as total")
                ->groupBy('result_name')
                ->pluck('total', 'result_name')
            : collect();

        return view('Warehouse::Livewire.inventory', [
            'canManageInventory' => $canManageInventory,
            'checks' => $canManageInventory
                ? InventoryCheck::query()
                    ->with('starter')
                    ->withCount('items')
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
            'check' => $check,
            'counts' => $counts,
            'statuses' => SlabStatus::options(),
            'items' => $check
                ? $check->items()
                    ->with(['slab.material', 'checker'])
                    ->when($this->resultFilter === 'unchecked', fn ($query) => $query->whereNull('result'))
                    ->when($this->resultFilter && $this->resultFilter !== 'unchecked', fn ($query) => $query->where('result', $this->resultFilter))
                    ->latest('checked_at')
                    ->orderBy('id')
                    ->paginate(10)
                : null,
        ]);
    }
}
