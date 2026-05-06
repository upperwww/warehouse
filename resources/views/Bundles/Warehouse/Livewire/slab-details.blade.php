<section class="page-shell space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('warehouse.slabs') }}" class="text-sm font-semibold text-zinc-500 hover:text-[#333333]">Back to items</a>
            <h1 class="mt-2 text-3xl font-bold text-[#333333]">{{ $slab->code }}</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ $slab->material->name }} · {{ $slab->barcode_value }}</p>
        </div>

        <div class="flex gap-2">
            <button onclick="window.print()" type="button" class="btn-secondary">Print label</button>
            @if (auth()->user()?->canManageWarehouse())
                <a href="{{ route('warehouse.slabs') }}" class="btn-success">Edit in items</a>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[340px_1fr]">
        <aside class="space-y-6">
            <div class="print-label panel p-5">
                <p class="label">Warehouse label</p>
                <h2 class="mt-2 text-2xl font-bold">{{ $slab->code }}</h2>
                <p class="text-sm text-zinc-600">{{ $slab->material->name }}</p>

                <div class="mt-4 rounded-md bg-white p-3 ring-1 ring-zinc-200">
                    @php
                        $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                    @endphp
                    {!! $barcodeGenerator->getBarcode($slab->barcode_value, $barcodeGenerator::TYPE_CODE_128, 1.4, 54) !!}
                </div>
                <p class="mt-3 break-all font-mono text-sm font-semibold">{{ $slab->barcode_value }}</p>

                <div class="mt-5">
                    <p class="label">QR detail</p>
                    <div class="mt-2 max-w-40 rounded-md bg-white p-2 ring-1 ring-zinc-200 [&_svg]:h-auto [&_svg]:w-full">
                        {!! $qrCodeSvg !!}
                    </div>
                </div>

                <dl class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-zinc-500">Location</dt>
                        <dd class="text-right">{{ $slab->location ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-zinc-500">Size</dt>
                        <dd class="text-right">{{ $slab->length_cm }} x {{ $slab->width_cm }} x {{ $slab->thickness_cm }} cm</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-zinc-500">Status</dt>
                        <dd class="text-right">{{ $slab->status->label() }}</dd>
                    </div>
                </dl>
            </div>
        </aside>

        <div class="space-y-6 no-print">
            <div class="panel p-5">
                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <p class="label">Status</p>
                        <span class="mt-1 inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $slab->status->color() }}">{{ $slab->status->label() }}</span>
                    </div>
                    <div>
                        <p class="label">Location</p>
                        <p class="mt-1 font-semibold">{{ $slab->location ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="label">Area</p>
                        <p class="mt-1 font-semibold">{{ number_format($slab->area_m2, 2) }} m2</p>
                    </div>
                    <div>
                        <p class="label">Added by</p>
                        <p class="mt-1 font-semibold">{{ $slab->creator?->name ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="label">Source</p>
                        <p class="mt-1 font-semibold">{{ $slab->source ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="label">Supplier</p>
                        <p class="mt-1 font-semibold">{{ $slab->supplier ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="label">Received at</p>
                        <p class="mt-1 font-semibold">{{ $slab->received_at?->format('d.m.Y H:i') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="label">Shipped at</p>
                        <p class="mt-1 font-semibold">{{ $slab->shipped_at?->format('d.m.Y H:i') ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="panel p-5">
                <h2 class="font-bold">Notes</h2>
                <p class="mt-3 rounded-lg bg-zinc-50 p-4 text-sm text-zinc-700">{{ $slab->notes ?: 'No notes.' }}</p>
            </div>

            @if ($canViewHistory)
                <div class="panel overflow-hidden" x-data="{ open: false }">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 border-b border-zinc-200 px-5 py-4 text-left hover:bg-zinc-50"
                        x-on:click="open = !open"
                        x-bind:aria-expanded="open.toString()"
                    >
                        <span>
                            <span class="block font-bold">Movement history</span>
                            <span class="mt-1 block text-xs font-medium text-zinc-500">Show item arrivals, updates, shipments and inventory status changes.</span>
                        </span>
                        <span class="rounded-md bg-[#FDD07D] px-2.5 py-1 text-xs font-bold text-[#333333]" x-text="open ? 'Hide' : 'Show'"></span>
                    </button>
                    <div x-cloak x-show="open" x-transition>
                    <div class="divide-y divide-zinc-100">
                        @forelse ($movements as $movement)
                            <div class="px-5 py-4 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold">{{ ucfirst($movement->action) }}</p>
                                    <p class="text-xs text-zinc-500">{{ $movement->created_at->format('d.m.Y H:i') }}</p>
                                </div>
                                <p class="mt-1 text-zinc-600">{{ $movement->description }}</p>
                                @if ($movement->changes)
                                    <div class="mt-2 space-y-1 text-xs text-zinc-600">
                                        @foreach ($movement->changes as $field => $change)
                                            <p><span class="font-semibold">{{ ucfirst($field) }}:</span> {{ $change['from'] ?? '-' }} -> {{ $change['to'] ?? '-' }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="px-5 py-8 text-sm text-zinc-500">No movement history.</p>
                        @endforelse
                    </div>
                    <div class="border-t border-zinc-200 px-5 py-4">
                        {{ $movements->links() }}
                    </div>
                    </div>
                </div>

                <div class="panel overflow-hidden" x-data="{ open: false }">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 border-b border-zinc-200 px-5 py-4 text-left hover:bg-zinc-50"
                        x-on:click="open = !open"
                        x-bind:aria-expanded="open.toString()"
                    >
                        <span>
                            <span class="block font-bold">Inventory history</span>
                            <span class="mt-1 block text-xs font-medium text-zinc-500">Show when this item was checked, found, missed or reported in the wrong place.</span>
                        </span>
                        <span class="rounded-md bg-[#FDD07D] px-2.5 py-1 text-xs font-bold text-[#333333]" x-text="open ? 'Hide' : 'Show'"></span>
                    </button>
                    <div x-cloak x-show="open" x-transition>
                    <div class="divide-y divide-zinc-100">
                        @forelse ($inventoryHistory as $item)
                            <div class="px-5 py-4 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold">{{ $item->inventoryCheck->name }}</p>
                                    <span class="rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-semibold">{{ str($item->result ?? 'unchecked')->replace('_', ' ')->title() }}</span>
                                </div>
                                <p class="mt-1 text-zinc-500">{{ $item->checked_at?->format('d.m.Y H:i') ?? '-' }} · {{ $item->checker?->name ?: 'Not checked' }}</p>
                            </div>
                        @empty
                            <p class="px-5 py-8 text-sm text-zinc-500">No inventory history.</p>
                        @endforelse
                    </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

