<section class="page-shell space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-[#333333]">{{ __('Inventory') }}</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ __('Scan item barcodes, confirm locations, and find missing warehouse stock.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 {{ $canManageInventory ? 'lg:grid-cols-[340px_1fr]' : '' }}">
        @if ($canManageInventory)
        <aside class="space-y-6">
            <form wire:submit="startInventory" class="panel p-5">
                <h2 class="font-bold">{{ __('Start inventory') }}</h2>
                <div class="mt-4">
                    <label class="label" for="inventory_name">{{ __('Name') }}</label>
                    <input wire:model="name" id="inventory_name" class="input mt-1" placeholder="{{ __('May warehouse check') }}">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-success mt-4 w-full">{{ __('Start inventory') }}</button>
            </form>

            <div class="panel overflow-hidden">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-bold">{{ __('Recent checks') }}</h2>
                </div>

                <div class="divide-y divide-zinc-100">
                    @forelse ($checks as $inventoryCheck)
                        <button wire:click="openCheck({{ $inventoryCheck->id }})" type="button" class="block w-full px-5 py-4 text-left hover:bg-zinc-50">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold">{{ $inventoryCheck->name }}</p>
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $inventoryCheck->status === 'active',
                                    'bg-zinc-200 text-zinc-700' => $inventoryCheck->status === 'completed',
                                ])>
                                        {{ __(ucfirst($inventoryCheck->status)) }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ $inventoryCheck->items_count }} items · {{ $inventoryCheck->starter?->name ?: 'System' }}
                            </p>
                        </button>
                    @empty
                        <p class="px-5 py-8 text-sm text-zinc-500">No inventory checks yet.</p>
                    @endforelse
                </div>
            </div>
        </aside>
        @endif

        <div class="space-y-6">
            @unless ($canManageInventory)
                <form wire:submit="startInventory" class="panel p-5">
                    <h2 class="font-bold">{{ __('Start inventory') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('Create a new check and start scanning warehouse items.') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto]">
                        <div>
                            <label class="label" for="worker_inventory_name">{{ __('Name') }}</label>
                            <input wire:model="name" id="worker_inventory_name" class="input mt-1" placeholder="{{ __('Warehouse check') }}">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="btn-success w-full">{{ __('Start inventory') }}</button>
                        </div>
                    </div>
                </form>
            @endunless

            @if ($check)
                <div class="panel p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold">{{ $check->name }}</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Started {{ $check->started_at?->format('d.m.Y H:i') ?? '-' }}
                            </p>
                        </div>

                        @if ($check->status === 'active')
                            <div class="flex gap-2">
                                <button wire:click="exportCsv" type="button" class="btn-secondary">{{ __('Export CSV') }}</button>
                                <button wire:click="completeInventory" wire:confirm="Complete inventory and mark unchecked items as missing?" type="button" class="btn-danger">
                                    {{ __('Complete inventory') }}
                                </button>
                            </div>
                        @else
                            <button wire:click="exportCsv" type="button" class="btn-secondary">{{ __('Export CSV') }}</button>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-5">
                        @foreach ([
                            'unchecked' => __('Unchecked'),
                            'found' => __('Found'),
                            'wrong_location' => __('Wrong location'),
                            'wrong_status' => __('Wrong status'),
                            'missing' => __('Missing'),
                        ] as $result => $label)
                            <button wire:click="$set('resultFilter', '{{ $result }}')" type="button" @class([
                                'rounded-lg border p-3 text-left text-sm',
                                'border-[#EB9800] bg-[#FDD07D]/40' => $resultFilter === $result,
                                'border-zinc-200 bg-zinc-50 hover:bg-white' => $resultFilter !== $result,
                            ])>
                                <p class="font-semibold">{{ $label }}</p>
                                <p class="mt-1 text-2xl font-bold">{{ $counts[$result] ?? 0 }}</p>
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($check->status === 'active')
                    <form wire:submit="scan" class="panel p-5">
                        <div class="grid gap-4 lg:grid-cols-[1fr_180px_180px]">
                            <div>
                                <label class="label" for="scanBarcode">{{ __('Scan barcode') }}</label>
                                <input wire:model="scanBarcode" id="scanBarcode" class="input mt-1 font-mono" placeholder="WH-000012 or item code" autofocus>
                                @error('scanBarcode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="label" for="actualLocation">{{ __('Actual location') }}</label>
                                <input wire:model="actualLocation" id="actualLocation" class="input mt-1" placeholder="{{ __('Leave empty if same') }}">
                                @error('actualLocation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="label" for="actualStatus">{{ __('Actual status') }}</label>
                                <select wire:model="actualStatus" id="actualStatus" class="input mt-1">
                                    <option value="">{{ __('Same as expected') }}</option>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('actualStatus') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_auto]">
                            <div>
                                <label class="label" for="note">{{ __('Note') }}</label>
                                <input wire:model="note" id="note" class="input mt-1" placeholder="{{ __('Optional inventory note') }}">
                                @error('note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-end">
                                <button type="submit" class="btn-success w-full">{{ __('Check item') }}</button>
                            </div>
                        </div>
                    </form>
                @endif

                <div class="panel overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                        <h2 class="font-bold">{{ __('Inventory items') }}</h2>
                        @if ($resultFilter)
                            <button wire:click="$set('resultFilter', '')" type="button" class="btn-secondary">{{ __('Clear filter') }}</button>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm">
                            <thead class="bg-emerald-50 text-left text-xs font-semibold uppercase text-zinc-600">
                                <tr>
                                    <th class="px-5 py-3">{{ __('Items') }}</th>
                                    <th class="px-5 py-3">{{ __('Barcode') }}</th>
                                    <th class="px-5 py-3">{{ __('Expected') }}</th>
                                    <th class="px-5 py-3">{{ __('Actual') }}</th>
                                    <th class="px-5 py-3">{{ __('Result') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Demo') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @forelse ($items as $item)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <p class="font-semibold">{{ $item->slab->code }}</p>
                                            <p class="text-xs text-zinc-500">{{ $item->slab->material->name }}</p>
                                        </td>
                                        <td class="px-5 py-4 font-mono text-xs">{{ $item->slab->barcode_value }}</td>
                                        <td class="px-5 py-4">
                                            <p>{{ $item->expected_location ?: '-' }}</p>
                                            <p class="text-xs text-zinc-500">{{ \App\Bundles\Warehouse\Utils\SlabStatus::tryFrom($item->expected_status)?->label() ?? $item->expected_status }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p>{{ $item->actual_location ?: '-' }}</p>
                                            <p class="text-xs text-zinc-500">{{ $item->actual_status ? (\App\Bundles\Warehouse\Utils\SlabStatus::tryFrom($item->actual_status)?->label() ?? $item->actual_status) : '-' }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            @php
                                                $result = $item->result ?? 'unchecked';
                                                $resultClasses = [
                                                    'unchecked' => 'bg-zinc-200 text-zinc-700',
                                                    'found' => 'bg-emerald-100 text-emerald-800',
                                                    'wrong_location' => 'bg-[#FDD07D] text-[#333333]',
                                                    'wrong_status' => 'bg-blue-100 text-blue-800',
                                                    'missing' => 'bg-red-100 text-red-800',
                                                    'damaged' => 'bg-red-100 text-red-800',
                                                ];
                                            @endphp
                                            <span class="rounded-md px-2.5 py-1 text-xs font-semibold {{ $resultClasses[$result] ?? 'bg-zinc-200 text-zinc-700' }}">
                                                {{ __(str($result)->replace('_', ' ')->title()->toString()) }}
                                            </span>
                                            @if ($item->checker)
                                                <p class="mt-1 text-xs text-zinc-500">{{ $item->checker->name }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            @if ($check->status === 'active')
                                                <button wire:click="useBarcode('{{ $item->slab->barcode_value }}')" type="button" class="btn-secondary">{{ __('Use barcode') }}</button>
                                            @else
                                                <span class="text-xs text-zinc-400">{{ __('Closed') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-10 text-center text-zinc-500">
                                            {{ __('No items in this inventory view.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($items)
                        <div class="border-t border-zinc-200 px-5 py-4">
                            {{ $items->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="panel p-10 text-center">
                    <h2 class="text-xl font-bold">{{ __('No inventory selected') }}</h2>
                    <p class="mt-2 text-sm text-zinc-500">
                        @if ($canManageInventory)
                            {{ __('Start an inventory check or open a recent one.') }}
                        @else
                            {{ __('There is no active inventory check right now.') }}
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</section>
