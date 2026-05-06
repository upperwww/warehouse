<section class="page-shell space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-[#333333]">{{ __('Dashboard') }}</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ __('Stock overview for materials and slabs.') }}</p>
        </div>

        <a href="{{ route('warehouse.slabs') }}" class="btn-primary">{{ __('Add or find slab') }}</a>
    </div>

    <div class="grid gap-4 md:grid-cols-6">
        <div class="panel p-5">
            <p class="label">{{ __('Materials') }}</p>
            <p class="mt-3 text-3xl font-bold">{{ $materialsCount }}</p>
        </div>
        <div class="panel p-5">
            <p class="label">{{ __('Total slabs') }}</p>
            <p class="mt-3 text-3xl font-bold">{{ $slabsCount }}</p>
        </div>
        <div class="panel border-[#FDD07D] bg-[#FFF7E6] p-5">
            <p class="label">{{ __('Available') }}</p>
            <p class="mt-3 text-3xl font-bold">{{ $availableCount }}</p>
        </div>
        <div class="panel p-5">
            <p class="label">{{ __('Damaged') }}</p>
            <p class="mt-3 text-3xl font-bold text-red-700">{{ $damagedCount }}</p>
        </div>
        <div class="panel p-5">
            <p class="label">{{ __('Missing') }}</p>
            <p class="mt-3 text-3xl font-bold text-red-700">{{ $missingCount }}</p>
        </div>
        <div class="panel p-5">
            <p class="label">{{ __('Total area') }}</p>
            <p class="mt-3 text-3xl font-bold">{{ number_format($totalArea, 2) }} m2</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_420px]">
        <div class="panel overflow-hidden">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="font-bold">{{ __('Recent slabs') }}</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-[#EDEDED] text-left text-xs font-semibold uppercase text-zinc-600">
                        <tr>
                            <th class="px-5 py-3">{{ __('Code') }}</th>
                            <th class="px-5 py-3">{{ __('Material') }}</th>
                            <th class="px-5 py-3">{{ __('Size') }}</th>
                            <th class="px-5 py-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($recentSlabs as $slab)
                            <tr>
                                <td class="px-5 py-4 font-semibold">{{ $slab->code }}</td>
                                <td class="px-5 py-4">{{ $slab->material->name }}</td>
                                <td class="px-5 py-4">{{ $slab->length_cm }} x {{ $slab->width_cm }} x {{ $slab->thickness_cm }} cm</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $slab->status->color() }}">
                                        {{ $slab->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-zinc-500">
                                    {{ __('No slabs yet. Add materials first, then create slabs.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="panel p-5">
                <h2 class="font-bold">{{ __('Stock by status') }}</h2>

                <div class="mt-5 space-y-4">
                    @foreach ($statuses as $status)
                        @php
                            $count = $statusCounts[$status->value] ?? 0;
                            $width = $slabsCount ? max(6, round(($count / $slabsCount) * 100)) : 0;
                        @endphp

                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-semibold">{{ $status->label() }}</span>
                                <span class="text-zinc-500">{{ $count }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-[#EDEDED]">
                                <div class="h-3 rounded-full bg-[#EB9800]" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="panel p-5">
                <h2 class="font-bold">{{ __('Latest inventory') }}</h2>
                @if ($latestInventory)
                    <p class="mt-3 font-semibold">{{ $latestInventory->name }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ ucfirst($latestInventory->status) }} · {{ $latestInventory->items_count }} items</p>
                    <a href="{{ route('warehouse.inventory') }}" class="btn-secondary mt-4">{{ __('Open inventory') }}</a>
                @else
                    <p class="mt-3 text-sm text-zinc-500">{{ __('No inventory checks yet.') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 {{ $canViewActivity ? 'lg:grid-cols-2' : '' }}">
        @if ($canViewActivity)
        <div class="panel overflow-hidden">
            <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-bold">{{ __('Warehouse activity') }}</h2>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($recentFlow as $movement)
                    <div class="px-5 py-4 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold">{{ ucfirst($movement->action) }} · {{ $movement->subject_name }}</p>
                            <p class="text-xs text-zinc-500">{{ $movement->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                        <p class="mt-1 text-zinc-600">{{ $movement->description }}</p>
                    </div>
                @empty
                        <p class="px-5 py-8 text-sm text-zinc-500">{{ __('No warehouse activity yet.') }}</p>
                @endforelse
            </div>
        </div>

        @endif

        <div class="panel overflow-hidden">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="font-bold">{{ __('Items needing attention') }}</h2>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($problemSlabs as $slab)
                    <div class="flex items-center justify-between gap-3 px-5 py-4 text-sm">
                        <div>
                            <p class="font-semibold">{{ $slab->code }}</p>
                            <p class="text-xs text-zinc-500">{{ $slab->material->name }} · {{ $slab->location ?: '-' }}</p>
                        </div>
                        <span class="rounded-md px-2.5 py-1 text-xs font-semibold {{ $slab->status->color() }}">
                            {{ $slab->status->label() }}
                        </span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-zinc-500">{{ __('No damaged or missing items. Nice.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
