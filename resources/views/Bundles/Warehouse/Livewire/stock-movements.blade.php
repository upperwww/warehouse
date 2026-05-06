<section class="page-shell space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#333333]">Stock movements</h1>
        <p class="mt-1 text-sm text-zinc-600">Every tracked warehouse change, including status and photo updates.</p>
    </div>

    <div class="panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-bold">
                {{ $movementType === 'item' ? 'Item changes' : 'Material changes' }}
            </h2>

            <div class="inline-flex rounded-md border border-zinc-200 bg-white p-1">
                <button
                    wire:click="showItems"
                    type="button"
                    @class([
                        'rounded px-3 py-1.5 text-sm font-semibold',
                        'bg-[#FDD07D] text-[#333333]' => $movementType === 'item',
                        'text-zinc-600 hover:bg-zinc-50' => $movementType !== 'item',
                    ])
                >
                    Item changes
                </button>
                <button
                    wire:click="showMaterials"
                    type="button"
                    @class([
                        'rounded px-3 py-1.5 text-sm font-semibold',
                        'bg-[#FDD07D] text-[#333333]' => $movementType === 'material',
                        'text-zinc-600 hover:bg-zinc-50' => $movementType !== 'material',
                    ])
                >
                    Material changes
                </button>
            </div>
        </div>

        <div class="grid gap-3 border-b border-zinc-200 px-5 py-4 md:grid-cols-[1fr_180px_180px_180px_180px_auto]">
            <input wire:model.live.debounce.300ms="search" class="input" placeholder="Search record or details">

            <select wire:model.live="actionFilter" class="input">
                <option value="">Filter by action</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                @endforeach
            </select>

            <select wire:model.live="roleFilter" class="input">
                <option value="">Filter by role</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <input wire:model.live="dateFrom" type="date" class="input">
            <input wire:model.live="dateTo" type="date" class="input">

            <button wire:click="clearFilters" type="button" class="btn-secondary">Clear</button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-emerald-50 text-left text-xs font-semibold uppercase text-zinc-600">
                    <tr>
                        <th class="px-5 py-3">Record</th>
                        <th class="px-5 py-3">Changed by</th>
                        <th class="px-5 py-3">Action</th>
                        <th class="px-5 py-3">Details</th>
                        <th class="px-5 py-3">Changed at</th>
                        <th class="px-5 py-3 text-right">View</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $movement->subject_name }}</p>
                                <p class="text-xs text-zinc-500">{{ ucfirst($movement->type) }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @if ($movement->actor)
                                    <div>
                                        <p class="font-semibold">{{ $movement->actor->name }}</p>
                                        <p class="text-xs text-zinc-500">{{ $movement->actor->email }}</p>
                                    </div>
                                @else
                                    <span class="text-zinc-500">System</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'rounded-md px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $movement->action === 'created',
                                    'bg-blue-100 text-blue-800' => in_array($movement->action, ['updated', 'inventory'], true),
                                    'bg-red-100 text-red-800' => in_array($movement->action, ['deleted', 'archived'], true),
                                    'bg-[#FDD07D] text-[#333333]' => in_array($movement->action, ['arrived', 'shipped'], true),
                                ])>
                                    {{ ucfirst($movement->action) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $movement->description }}</p>

                                @if ($movement->changes)
                                    <div class="mt-2 space-y-1 text-xs text-zinc-600">
                                        @foreach ($movement->changes as $field => $change)
                                            <p>
                                                <span class="font-semibold">{{ ucfirst($field) }}:</span>
                                                <span>{{ $change['from'] ?? '-' }}</span>
                                                <span class="text-zinc-400">-></span>
                                                <span>{{ $change['to'] ?? '-' }}</span>
                                            </p>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">{{ $movement->created_at->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-4 text-right">
                                <button wire:click="showDetails({{ $movement->id }})" type="button" class="btn-secondary">Details</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-zinc-500">
                                No changes found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-5 py-4">
            {{ $movements->links() }}
        </div>
    </div>

    @if ($selectedMovement)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 px-4 py-6">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">{{ ucfirst($selectedMovement->action) }} · {{ $selectedMovement->subject_name }}</h2>
                        <p class="mt-1 text-sm text-zinc-500">{{ $selectedMovement->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                    <button wire:click="closeDetails" type="button" class="rounded-md px-2 py-1 text-xl leading-none text-zinc-500 hover:bg-zinc-100">&times;</button>
                </div>

                <dl class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <dt class="label">Changed by</dt>
                        <dd class="mt-1 font-semibold">{{ $selectedMovement->actor?->name ?: 'System' }}</dd>
                    </div>
                    <div>
                        <dt class="label">Type</dt>
                        <dd class="mt-1 font-semibold">{{ ucfirst($selectedMovement->type) }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="label">Description</dt>
                        <dd class="mt-1 rounded-lg bg-zinc-50 p-4 text-sm text-zinc-700">{{ $selectedMovement->description }}</dd>
                    </div>
                </dl>

                @if ($selectedMovement->changes)
                    <div class="mt-5">
                        <h3 class="font-bold">Changed values</h3>
                        <div class="mt-3 divide-y divide-zinc-100 rounded-lg border border-zinc-200">
                            @foreach ($selectedMovement->changes as $field => $change)
                                <div class="grid gap-3 p-3 text-sm md:grid-cols-[160px_1fr_1fr]">
                                    <p class="font-semibold">{{ ucfirst($field) }}</p>
                                    <p><span class="text-zinc-500">From:</span> {{ $change['from'] ?? '-' }}</p>
                                    <p><span class="text-zinc-500">To:</span> {{ $change['to'] ?? '-' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <button wire:click="closeDetails" type="button" class="btn-secondary">Close</button>
                </div>
            </div>
        </div>
    @endif
</section>
