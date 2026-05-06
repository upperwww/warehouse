<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Models\InventoryCheckItem;
use App\Bundles\Warehouse\Models\Slab;
use App\Bundles\Warehouse\Models\StockMovement;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SlabDetails extends Component
{
    public Slab $slab;

    public function mount(Slab $slab): void
    {
        $this->slab = $slab->load(['material', 'creator']);
    }

    public function qrCodeSvg(): string
    {
        $qrCode = new QrCode(route('warehouse.slabs.show', $this->slab));

        return (new SvgWriter())
            ->write($qrCode, options: [SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true])
            ->getString();
    }

    public function render()
    {
        $canViewHistory = auth()->user()?->hasAnyRole(['Admin', 'Manager']) ?? false;

        return view('Warehouse::Livewire.slab-details', [
            'canViewHistory' => $canViewHistory,
            'movements' => $canViewHistory
                ? StockMovement::query()
                    ->with('actor')
                    ->where('type', 'item')
                    ->where('subject_id', $this->slab->id)
                    ->latest()
                    ->paginate(8)
                : null,
            'inventoryHistory' => $canViewHistory
                ? InventoryCheckItem::query()
                    ->with(['inventoryCheck', 'checker'])
                    ->where('slab_id', $this->slab->id)
                    ->latest()
                    ->take(8)
                    ->get()
                : collect(),
            'qrCodeSvg' => $this->qrCodeSvg(),
        ]);
    }
}
