<section class="page-shell space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#333333]">Item flow</h1>
        <p class="mt-1 text-sm text-zinc-600">Receive new items and mark shipped items without digging through generic edit forms.</p>
    </div>

    <div class="panel overflow-hidden">
        <div class="flex border-b border-zinc-200 p-4">
            <button wire:click="showReceive" type="button" @class([
                'rounded-md px-4 py-2 text-sm font-semibold',
                'bg-[#FDD07D] text-[#333333]' => $mode === 'receive',
                'text-zinc-600 hover:bg-zinc-100' => $mode !== 'receive',
            ])>
                Receive item
            </button>
            <button wire:click="showShip" type="button" @class([
                'rounded-md px-4 py-2 text-sm font-semibold',
                'bg-[#FDD07D] text-[#333333]' => $mode === 'ship',
                'text-zinc-600 hover:bg-zinc-100' => $mode !== 'ship',
            ])>
                Ship item
            </button>
        </div>

        @if ($mode === 'receive')
            <form wire:submit="receive" class="grid gap-5 p-6 lg:grid-cols-2">
                <div>
                    <label class="label" for="material_id">Material</label>
                    <select wire:model="material_id" id="material_id" class="input mt-1">
                        <option value="">Choose material</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}">{{ $material->name }}</option>
                        @endforeach
                    </select>
                    @error('material_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="code">Item code</label>
                    <input wire:model="code" id="code" class="input mt-1" placeholder="CAR-004">
                    @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="barcode">Barcode</label>
                    <input wire:model="barcode" id="barcode" class="input mt-1" placeholder="Auto-generated if empty">
                    @error('barcode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="received_at">Received date</label>
                    <input wire:model="received_at" id="received_at" type="datetime-local" class="input mt-1">
                    @error('received_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-3 gap-3 lg:col-span-2">
                    <div>
                        <label class="label" for="length_cm">Length</label>
                        <input wire:model="length_cm" id="length_cm" type="number" min="1" class="input mt-1">
                        @error('length_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="width_cm">Width</label>
                        <input wire:model="width_cm" id="width_cm" type="number" min="1" class="input mt-1">
                        @error('width_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="thickness_cm">Thick.</label>
                        <input wire:model="thickness_cm" id="thickness_cm" type="number" min="1" class="input mt-1">
                        @error('thickness_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="label" for="source">Source</label>
                    <input wire:model="source" id="source" class="input mt-1" placeholder="Italy quarry, supplier warehouse, return">
                    @error('source') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="supplier">Supplier</label>
                    <input wire:model="supplier" id="supplier" class="input mt-1" placeholder="Supplier name">
                    @error('supplier') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="location">Location</label>
                    <input wire:model="location" id="location" class="input mt-1" placeholder="Rack A3">
                    @error('location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="notes">Note</label>
                    <input wire:model="notes" id="notes" class="input mt-1" placeholder="Optional receiving note">
                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="lg:col-span-2 flex justify-end">
                    <button type="submit" class="btn-success">Receive item</button>
                </div>
            </form>
        @else
            <form wire:submit="ship" class="grid gap-5 p-6 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label class="label" for="slab_id">Item</label>
                    <select wire:model="slab_id" id="slab_id" class="input mt-1">
                        <option value="">Choose item to ship</option>
                        @foreach ($availableSlabs as $slab)
                            <option value="{{ $slab->id }}">{{ $slab->code }} - {{ $slab->material->name }} - {{ $slab->location ?: 'No location' }}</option>
                        @endforeach
                    </select>
                    @error('slab_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="shipped_at">Shipped date</label>
                    <input wire:model="shipped_at" id="shipped_at" type="datetime-local" class="input mt-1">
                    @error('shipped_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="ship_notes">Shipment note</label>
                    <input wire:model="notes" id="ship_notes" class="input mt-1" placeholder="Customer/order note">
                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="lg:col-span-2 flex justify-end">
                    <button wire:confirm="Mark this item as shipped?" type="submit" class="btn-danger">Ship item</button>
                </div>
            </form>
        @endif
    </div>
</section>
