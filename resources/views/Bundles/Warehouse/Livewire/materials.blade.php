<section class="page-shell space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-[#333333]">Materials</h1>
            <p class="mt-1 text-sm text-zinc-600">Surface categories used by slabs in stock.</p>
        </div>

        <button wire:click="create" type="button" class="btn-success">Add Material +</button>
    </div>

    <div class="panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-bold">Materials ({{ $materials->total() }})</h2>
            <input wire:model.live.debounce.300ms="search" class="input max-w-sm" placeholder="Search materials">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-emerald-50 text-left text-xs font-semibold uppercase text-zinc-600">
                    <tr>
                        <th class="px-5 py-3">Material</th>
                        <th class="px-5 py-3">Slabs</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($materials as $material)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($material->image_path)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($material->image_path) }}"
                                            alt="{{ $material->name }}"
                                            class="size-11 rounded-full object-cover ring-1 ring-zinc-200"
                                        >
                                    @else
                                        <span class="grid size-11 place-items-center rounded-full bg-[#FDD07D] text-xs font-bold text-[#333333]">
                                            {{ str($material->name)->substr(0, 2)->upper() }}
                                        </span>
                                    @endif

                                    <div>
                                        <p class="font-semibold">{{ $material->name }}</p>
                                        @if ($material->description)
                                            <p class="mt-1 max-w-xl truncate text-xs text-zinc-500">{{ $material->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">{{ $material->slabs_count }}</td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $material->is_active,
                                    'bg-zinc-200 text-zinc-700' => ! $material->is_active,
                                ])>
                                    {{ $material->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $material->id }})" class="btn-secondary" type="button">Edit</button>
                                    <button wire:click="delete({{ $material->id }})" wire:confirm="Archive this material?" class="btn-danger" type="button">
                                        Archive
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-zinc-500">
                                No materials found. Add the first surface category.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-5 py-4">
            {{ $materials->links() }}
        </div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 px-4 py-6">
            <form wire:submit="save" class="w-full max-w-xl rounded-lg bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-xl font-bold">{{ $editingId ? 'Edit material' : 'Add material' }}</h2>
                    <button wire:click="closeModal" type="button" class="rounded-md px-2 py-1 text-xl leading-none text-zinc-500 hover:bg-zinc-100">&times;</button>
                </div>

                <div class="grid gap-4">
                    <div>
                        <label class="label" for="name">Name</label>
                        <input wire:model="name" id="name" class="input mt-1">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="description">Description</label>
                        <textarea wire:model="description" id="description" rows="3" class="input mt-1"></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <p class="label">Preview</p>
                        <div class="mt-1 grid size-24 place-items-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="size-full object-cover" alt="Preview">
                            @elseif ($existingImagePath)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($existingImagePath) }}" class="size-full object-cover" alt="Current image">
                            @else
                                <span class="text-xs font-semibold text-zinc-400">No photo</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="label" for="photo">Photo</label>
                        <input wire:model="photo" id="photo" type="file" accept="image/*" class="input mt-1">
                        @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input wire:model="is_active" type="checkbox" class="rounded border-zinc-300 text-[#EB9800] focus:ring-[#EB9800]">
                        Active material
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="closeModal" type="button" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-success">Save Material</button>
                </div>
            </form>
        </div>
    @endif

    @if ($showReplacementModal)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 px-4 py-6">
            <form wire:submit="replaceAndDelete" class="w-full max-w-xl rounded-lg bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Move items before archiving</h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            {{ $deletingMaterial?->name }} is used by {{ $replacementSlabCount }} item(s).
                        </p>
                    </div>
                    <button wire:click="closeReplacementModal" type="button" class="rounded-md px-2 py-1 text-xl leading-none text-zinc-500 hover:bg-zinc-100">&times;</button>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Choose a replacement material. Items will stay in stock and only their material will be changed.
                </div>

                <div class="mt-5">
                    <label class="label" for="replacementMaterialId">Replacement material</label>
                    <select wire:model="replacementMaterialId" id="replacementMaterialId" class="input mt-1">
                        @foreach ($replacementMaterials as $replacementMaterial)
                            <option value="{{ $replacementMaterial->id }}">{{ $replacementMaterial->name }}</option>
                        @endforeach
                    </select>
                    @error('replacementMaterialId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="closeReplacementModal" type="button" class="btn-secondary">Cancel</button>
                    <button
                        wire:confirm="Move these items and archive {{ $deletingMaterial?->name }}?"
                        type="submit"
                        class="btn-danger"
                    >
                        Move items and archive
                    </button>
                </div>
            </form>
        </div>
    @endif
</section>
