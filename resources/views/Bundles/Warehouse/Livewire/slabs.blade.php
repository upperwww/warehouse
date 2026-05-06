<section class="page-shell space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-[#333333]">{{ __('Items') }} ({{ $slabs->total() }})</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ __('Physical stone slabs in the warehouse.') }}</p>
        </div>

        @if (auth()->user()?->canManageWarehouse())
            <div class="flex gap-2">
                <button wire:click="exportCsv" type="button" class="btn-secondary">{{ __('Export CSV') }}</button>
                <button wire:click="create" type="button" class="btn-success">{{ __('Add Item +') }}</button>
            </div>
        @else
            <button wire:click="exportCsv" type="button" class="btn-secondary">{{ __('Export CSV') }}</button>
        @endif
    </div>

    <div class="flex flex-col gap-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-zinc-200">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-5 text-sm font-semibold">
                <span class="inline-flex items-center gap-2">
                    <span class="status-dot bg-emerald-500"></span>
                    {{ __('In Stock') }} ({{ $statusCounts['available'] ?? 0 }})
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="status-dot bg-amber-400"></span>
                    {{ __('Reserved') }} ({{ $statusCounts['reserved'] ?? 0 }})
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="status-dot bg-red-500"></span>
                    {{ __('Out of Stock') }} ({{ ($statusCounts['sold'] ?? 0) + ($statusCounts['damaged'] ?? 0) + ($statusCounts['missing'] ?? 0) }})
                </span>
            </div>

            <div class="grid w-full gap-3 sm:w-auto sm:grid-cols-[260px_180px_220px_auto]">
                <input wire:model.live.debounce.300ms="search" class="input" placeholder="{{ __('Search item') }}">

                <select wire:model.live="statusFilter" class="input">
                    <option value="">{{ __('Filter By Status') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="materialFilter" class="input">
                    <option value="">{{ __('Filter By Category') }}</option>
                    @foreach ($materials as $material)
                        <option value="{{ $material->id }}">{{ $material->name }}</option>
                    @endforeach
                </select>

                <button wire:click="clearFilters" type="button" class="btn-secondary whitespace-nowrap">
                    {{ __('Clear filters') }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-emerald-50 text-left text-xs font-semibold uppercase text-zinc-600">
                    <tr>
                        <th class="px-5 py-3">{{ __('Item Name') }}</th>
                        <th class="px-5 py-3">{{ __('Barcode') }}</th>
                        <th class="px-5 py-3">{{ __('Category') }}</th>
                        <th class="px-5 py-3">{{ __('Current Size') }}</th>
                        <th class="px-5 py-3">{{ __('Area') }}</th>
                        <th class="px-5 py-3">{{ __('Location') }}</th>
                        <th class="px-5 py-3">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($slabs as $slab)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($slab->material->image_path)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($slab->material->image_path) }}"
                                            alt="{{ $slab->material->name }}"
                                            class="size-11 rounded-full object-cover ring-1 ring-zinc-200"
                                        >
                                    @else
                                        <span class="grid size-11 place-items-center rounded-full bg-[#FDD07D] text-xs font-bold text-[#333333]">
                                            {{ str($slab->code)->substr(0, 2)->upper() }}
                                        </span>
                                    @endif

                                    <div>
                                        <p class="font-semibold">{{ $slab->code }}</p>
                                        <p class="text-xs text-zinc-500">{{ $slab->material->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">{{ $slab->barcode_value }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 text-xs font-semibold">
                                    {{ $slab->material->name }}
                                </span>
                            </td>
                            <td class="px-5 py-4">{{ $slab->length_cm }} x {{ $slab->width_cm }} x {{ $slab->thickness_cm }} cm</td>
                            <td class="px-5 py-4">{{ number_format($slab->area_m2, 2) }} m2</td>
                            <td class="px-5 py-4">{{ $slab->location ?: '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-md px-2.5 py-1 text-xs font-semibold {{ $slab->status->color() }}">
                                    {{ $slab->status->label() }}
                                </span>
                            </td>
                            @if (auth()->user()?->canManageWarehouse())
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('warehouse.slabs.show', $slab) }}" class="btn-secondary">{{ __('Details') }}</a>
                                        <button wire:click="edit({{ $slab->id }})" class="btn-secondary" type="button">{{ __('Edit') }}</button>
                                        <button wire:click="delete({{ $slab->id }})" wire:confirm="Archive slab {{ $slab->code }}?" class="btn-danger" type="button">
                                            {{ __('Archive') }}
                                        </button>
                                    </div>
                                </td>
                            @else
                                <td class="px-5 py-4">
                                    <a href="{{ route('warehouse.slabs.show', $slab) }}" class="btn-secondary">{{ __('Details') }}</a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-zinc-500">
                                {{ __('No items match this search. Clear filters or add a new item.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 pt-4">
            {{ $slabs->links() }}
        </div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 px-4 py-6">
            <form wire:submit="save" class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-xl font-bold">{{ $editingId ? __('Edit item') : __('Add item') }}</h2>
                    <button wire:click="closeModal" type="button" class="rounded-md px-2 py-1 text-xl leading-none text-zinc-500 hover:bg-zinc-100">&times;</button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label" for="material">{{ __('Category') }}</label>
                        <select wire:model="material_id" id="material" class="input mt-1">
                            <option value="">{{ __('Choose material') }}</option>
                            @foreach ($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                            @endforeach
                        </select>
                        @error('material_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="code">{{ __('Item name / code') }}</label>
                        <input wire:model="code" id="code" class="input mt-1" placeholder="CAR-001">
                        @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="barcode">{{ __('Barcode') }}</label>
                        <input wire:model="barcode" id="barcode" class="input mt-1" placeholder="{{ __('Auto-generated if empty') }}">
                        @error('barcode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-3 md:col-span-2">
                        <div>
                            <label class="label" for="length">{{ __('Length') }}</label>
                            <input wire:model="length_cm" id="length" type="number" min="1" class="input mt-1">
                            @error('length_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="width">{{ __('Width') }}</label>
                            <input wire:model="width_cm" id="width" type="number" min="1" class="input mt-1">
                            @error('width_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="thickness">{{ __('Thick.') }}</label>
                            <input wire:model="thickness_cm" id="thickness" type="number" min="1" class="input mt-1">
                            @error('thickness_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label" for="status">{{ __('Status') }}</label>
                        <select wire:model="status" id="status" class="input mt-1">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="location">{{ __('Location') }}</label>
                        <input wire:model="location" id="location" class="input mt-1" placeholder="Rack A3">
                        @error('location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="source">{{ __('Source') }}</label>
                        <input wire:model="source" id="source" class="input mt-1" placeholder="Italy quarry">
                        @error('source') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="supplier">{{ __('Supplier') }}</label>
                        <input wire:model="supplier" id="supplier" class="input mt-1" placeholder="Supplier name">
                        @error('supplier') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="received_at">{{ __('Received at') }}</label>
                        <input wire:model="received_at" id="received_at" type="datetime-local" class="input mt-1">
                        @error('received_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="shipped_at">{{ __('Shipped at') }}</label>
                        <input wire:model="shipped_at" id="shipped_at" type="datetime-local" class="input mt-1">
                        @error('shipped_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="label" for="notes">{{ __('Notes') }}</label>
                        <textarea wire:model="notes" id="notes" rows="3" class="input mt-1"></textarea>
                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="closeModal" type="button" class="btn-secondary">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn-success">{{ __('Save Item') }}</button>
                </div>
            </form>
        </div>
    @endif

    @if ($showDetailsModal && $detailsSlab)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 px-4 py-6">
            <div class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-lg bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold">{{ $detailsSlab->code }}</h2>
                        <p class="mt-1 text-sm text-zinc-500">{{ $detailsSlab->material->name }} item details and movement history.</p>
                    </div>
                    <div class="no-print flex items-center gap-2">
                        <button onclick="window.print()" type="button" class="btn-secondary">Print label</button>
                        <button wire:click="closeDetailsModal" type="button" class="rounded-md px-2 py-1 text-xl leading-none text-zinc-500 hover:bg-zinc-100">&times;</button>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
                    <aside class="print-label rounded-lg border border-zinc-200 bg-zinc-50 p-5">
                        <p class="label">Warehouse label</p>
                        <h3 class="mt-2 text-2xl font-bold">{{ $detailsSlab->code }}</h3>
                        <p class="text-sm text-zinc-600">{{ $detailsSlab->material->name }}</p>
                        <div class="mt-3 rounded-md bg-white p-3 ring-1 ring-zinc-200">
                            @php
                                $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                            @endphp
                            {!! $barcodeGenerator->getBarcode($detailsSlab->barcode_value, $barcodeGenerator::TYPE_CODE_128, 1.4, 54) !!}
                        </div>
                        <p class="mt-3 break-all font-mono text-sm font-semibold">{{ $detailsSlab->barcode_value }}</p>
                        <dl class="mt-4 grid gap-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="font-semibold text-zinc-500">Location</dt>
                                <dd>{{ $detailsSlab->location ?: '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="font-semibold text-zinc-500">Size</dt>
                                <dd>{{ $detailsSlab->length_cm }} x {{ $detailsSlab->width_cm }} x {{ $detailsSlab->thickness_cm }} cm</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="font-semibold text-zinc-500">Status</dt>
                                <dd>{{ $detailsSlab->status->label() }}</dd>
                            </div>
                        </dl>
                    </aside>

                    <div class="no-print space-y-5">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <p class="label">Status</p>
                                <span class="mt-1 inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $detailsSlab->status->color() }}">
                                    {{ $detailsSlab->status->label() }}
                                </span>
                            </div>
                            <div>
                                <p class="label">Location</p>
                                <p class="mt-1 font-semibold">{{ $detailsSlab->location ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="label">Area</p>
                                <p class="mt-1 font-semibold">{{ number_format($detailsSlab->area_m2, 2) }} m2</p>
                            </div>
                            <div>
                                <p class="label">Source</p>
                                <p class="mt-1 font-semibold">{{ $detailsSlab->source ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="label">Supplier</p>
                                <p class="mt-1 font-semibold">{{ $detailsSlab->supplier ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="label">Added by</p>
                                <p class="mt-1 font-semibold">{{ $detailsSlab->creator?->name ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="label">Received at</p>
                                <p class="mt-1 font-semibold">{{ $detailsSlab->received_at?->format('d.m.Y H:i') ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="label">Shipped at</p>
                                <p class="mt-1 font-semibold">{{ $detailsSlab->shipped_at?->format('d.m.Y H:i') ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="label">Size</p>
                                <p class="mt-1 font-semibold">{{ $detailsSlab->length_cm }} x {{ $detailsSlab->width_cm }} x {{ $detailsSlab->thickness_cm }} cm</p>
                            </div>
                        </div>

                        <div>
                            <p class="label">Notes</p>
                            <p class="mt-1 rounded-lg bg-zinc-50 p-4 text-sm text-zinc-700">{{ $detailsSlab->notes ?: 'No notes.' }}</p>
                        </div>

                        <div>
                            <h3 class="font-bold">Latest changes</h3>
                            <div class="mt-3 divide-y divide-zinc-100 rounded-lg border border-zinc-200">
                                @forelse ($detailsMovements as $movement)
                                    <div class="p-3 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="font-semibold">{{ ucfirst($movement->action) }}</p>
                                            <p class="text-xs text-zinc-500">{{ $movement->created_at->format('d.m.Y H:i') }}</p>
                                        </div>
                                        <p class="mt-1 text-zinc-600">{{ $movement->description }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">By {{ $movement->actor?->name ?: 'System' }}</p>
                                    </div>
                                @empty
                                    <p class="p-4 text-sm text-zinc-500">No movement history yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>
