<section class="page-shell space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-[#333333]">Employees</h1>
            <p class="mt-1 text-sm text-zinc-600">Manage company users and their roles.</p>
        </div>

        <button wire:click="create" type="button" class="btn-success">Add employee +</button>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($roles as $role => $label)
            @php
                $roleEnum = \App\Bundles\Warehouse\Utils\UserRole::from($role);
            @endphp

            <div class="panel p-5">
                <p class="label">{{ $label }}</p>
                <div class="mt-3 flex items-center justify-between">
                    <p class="text-3xl font-bold">{{ $roleCounts[$role] ?? 0 }}</p>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleEnum->badge() }}">{{ $label }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel overflow-hidden">
        <div class="grid gap-3 border-b border-zinc-200 px-5 py-4 md:grid-cols-[1fr_220px]">
            <input wire:model.live.debounce.300ms="search" class="input" placeholder="Search employee">

            <select wire:model.live="roleFilter" class="input">
                <option value="">Filter by role</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-emerald-50 text-left text-xs font-semibold uppercase text-zinc-600">
                    <tr>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Created at</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($employees as $employee)
                        @php
                            $roleName = $employee->roles->first()?->name ?? 'Worker';
                            $roleEnum = \App\Bundles\Warehouse\Utils\UserRole::tryFrom($roleName) ?? \App\Bundles\Warehouse\Utils\UserRole::Worker;
                        @endphp

                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($employee->avatar_path)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($employee->avatar_path) }}"
                                            alt="{{ $employee->name }}"
                                            class="size-11 rounded-full object-cover ring-1 ring-zinc-200"
                                        >
                                    @else
                                        <span class="grid size-11 place-items-center rounded-full bg-[#FDD07D] text-xs font-bold text-[#333333]">
                                            {{ str($employee->name)->substr(0, 2)->upper() }}
                                        </span>
                                    @endif
                                    <p class="font-semibold">{{ $employee->name }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4">{{ $employee->email }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleEnum->badge() }}">
                                    {{ $roleName }}
                                </span>
                            </td>
                            <td class="px-5 py-4">{{ $employee->created_at->format('d.m.Y') }}</td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $employee->is_active,
                                    'bg-zinc-200 text-zinc-700' => ! $employee->is_active,
                                ])>
                                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('warehouse.employees.profile', $employee) }}" class="btn-secondary">View profile</a>
                                    <button wire:click="edit({{ $employee->id }})" class="btn-secondary" type="button">Edit</button>
                                    <button
                                        wire:click="delete({{ $employee->id }})"
                                        wire:confirm="Delete {{ $employee->name }}?"
                                        class="btn-danger"
                                        type="button"
                                        @disabled($employee->is(auth()->user()))
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-zinc-500">
                                No employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-5 py-4">
            {{ $employees->links() }}
        </div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 px-4 py-6">
            <form wire:submit="save" class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-xl font-bold">{{ $editingId ? 'Edit employee' : 'Add employee' }}</h2>
                    <button wire:click="closeModal" type="button" class="rounded-md px-2 py-1 text-xl leading-none text-zinc-500 hover:bg-zinc-100">&times;</button>
                </div>

                <div class="grid gap-4">
                    <div>
                        <label class="label" for="name">Name</label>
                        <input wire:model="name" id="name" class="input mt-1">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="email">Email</label>
                        <input wire:model="email" id="email" type="email" class="input mt-1">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="role">Role</label>
                        <select wire:model="role" id="role" class="input mt-1">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input wire:model="is_active" type="checkbox" class="rounded border-zinc-300 text-[#EB9800] focus:ring-[#EB9800]">
                        Active account
                    </label>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="label" for="phone">Phone</label>
                            <input wire:model="phone" id="phone" class="input mt-1">
                            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="position">Position</label>
                            <input wire:model="position" id="position" class="input mt-1">
                            @error('position') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="department">Department</label>
                            <input wire:model="department" id="department" class="input mt-1">
                            @error('department') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label" for="password">Password</label>
                            <input wire:model="password" id="password" type="password" class="input mt-1" autocomplete="new-password">
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="password_confirmation">Confirm password</label>
                            <input wire:model="password_confirmation" id="password_confirmation" type="password" class="input mt-1" autocomplete="new-password">
                        </div>
                    </div>

                    @if ($editingId)
                        <p class="text-xs text-zinc-500">Leave password empty to keep the current password.</p>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="closeModal" type="button" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-success">Save employee</button>
                </div>
            </form>
        </div>
    @endif
</section>
