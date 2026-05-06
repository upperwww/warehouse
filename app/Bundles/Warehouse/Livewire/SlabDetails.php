<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Models\Slab;
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
        $canViewHistory = auth()->user()?->canViewWarehouseHistory() ?? false;

        return view('Warehouse::Livewire.slab-details', [
            'canViewHistory' => $canViewHistory,
            'movements' => $canViewHistory
                ? $this->slab
                    ->stockMovements()
                    ->with('actor')
                    ->latest()
                    ->paginate(8)
                : null,
            'inventoryHistory' => $canViewHistory
                ? $this->slab
                    ->inventoryItems()
                    ->with(['inventoryCheck', 'checker'])
                    ->latest()
                    ->take(8)
                    ->get()
                : collect(),
            'qrCodeSvg' => $this->qrCodeSvg(),
        ]);
    }
}
