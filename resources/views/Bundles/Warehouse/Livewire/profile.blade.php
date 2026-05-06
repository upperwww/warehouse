<section class="page-shell space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#333333]">Profile</h1>
        <p class="mt-1 text-sm text-zinc-600">View account and employee information.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <aside class="panel p-6">
            <div class="flex flex-col items-center text-center">
                <div class="grid size-32 place-items-center overflow-hidden rounded-full bg-[#FDD07D] text-3xl font-bold text-[#333333] ring-1 ring-zinc-200">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" alt="Photo preview" class="size-full object-cover">
                    @elseif ($profileUser->avatar_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($profileUser->avatar_path) }}" alt="{{ $profileUser->name }}" class="size-full object-cover">
                    @else
                        {{ str($profileUser->name)->substr(0, 2)->upper() }}
                    @endif
                </div>

                <h2 class="mt-4 text-xl font-bold">{{ $profileUser->name }}</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ $profileUser->email }}</p>

                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleBadge }}">{{ $roleName }}</span>
                    <span @class([
                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                        'bg-emerald-100 text-emerald-800' => $profileUser->is_active,
                        'bg-zinc-200 text-zinc-700' => ! $profileUser->is_active,
                    ])>
                        {{ $profileUser->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            @if ($canEditOwnProfile)
                <button wire:click="editProfile" type="button" class="btn-success mt-6 w-full">Edit profile</button>
            @endif

            @if ($canManagePhoto)
                <form wire:submit="savePhoto" class="mt-6 space-y-3 border-t border-zinc-200 pt-5">
                    <div>
                        <label class="label" for="profile_photo">Profile photo</label>
                        <input wire:model="photo" id="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="input mt-1">
                        @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <p class="text-xs text-zinc-500">
                        @if ($canEditOwnProfile)
                            You can edit your admin profile here.
                        @else
                            Only your profile photo can be changed. Other information is managed by Admin.
                        @endif
                    </p>

                    <div class="flex gap-2">
                        <button type="submit" class="btn-success flex-1">Upload photo</button>
                        @if ($profileUser->avatar_path)
                            <button
                                wire:click="deletePhoto"
                                wire:confirm="Delete your profile photo?"
                                type="button"
                                class="btn-danger"
                            >
                                Delete
                            </button>
                        @endif
                    </div>
                </form>
            @else
                <div class="mt-6 rounded-lg bg-zinc-50 p-4 text-sm text-zinc-600">
                    Profile photo can be changed only by this user.
                </div>
            @endif
        </aside>

        <div class="space-y-6">
            <div class="panel p-6">
                <h2 class="font-bold">Personal information</h2>
                <dl class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <dt class="label">Full name</dt>
                        <dd class="mt-1 font-semibold">{{ $profileUser->name }}</dd>
                    </div>
                    <div>
                        <dt class="label">Email</dt>
                        <dd class="mt-1 font-semibold">{{ $profileUser->email }}</dd>
                    </div>
                    <div>
                        <dt class="label">Phone</dt>
                        <dd class="mt-1 font-semibold">{{ $profileUser->phone ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="label">Role</dt>
                        <dd class="mt-1">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleBadge }}">{{ $roleName }}</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="panel p-6">
                <h2 class="font-bold">Work information</h2>
                <dl class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <dt class="label">Position</dt>
                        <dd class="mt-1 font-semibold">{{ $profileUser->position ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="label">Department</dt>
                        <dd class="mt-1 font-semibold">{{ $profileUser->department ?: '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="panel p-6">
                <h2 class="font-bold">Account information</h2>
                <dl class="mt-5 grid gap-4 md:grid-cols-3">
                    <div>
                        <dt class="label">Status</dt>
                        <dd class="mt-1 font-semibold">{{ $profileUser->is_active ? 'Active' : 'Inactive' }}</dd>
                    </div>
                    <div>
                        <dt class="label">Created at</dt>
                        <dd class="mt-1 font-semibold">{{ $profileUser->created_at?->format('d.m.Y H:i') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="label">Last updated</dt>
                        <dd class="mt-1 font-semibold">{{ $profileUser->updated_at?->format('d.m.Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    @if ($showProfileModal)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 px-4 py-6">
            <form wire:submit="saveProfile" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Edit profile</h2>
                        <p class="mt-1 text-sm text-zinc-500">This edits your own Admin profile only.</p>
                    </div>
                    <button wire:click="closeProfileModal" type="button" class="rounded-md px-2 py-1 text-xl leading-none text-zinc-500 hover:bg-zinc-100">&times;</button>
                </div>

                <div class="grid gap-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label" for="profile_name">Name</label>
                            <input wire:model="name" id="profile_name" class="input mt-1">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="profile_email">Email</label>
                            <input wire:model="email" id="profile_email" type="email" class="input mt-1">
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="label" for="profile_phone">Phone</label>
                            <input wire:model="phone" id="profile_phone" class="input mt-1">
                            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="profile_position">Position</label>
                            <input wire:model="position" id="profile_position" class="input mt-1">
                            @error('position') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="profile_department">Department</label>
                            <input wire:model="department" id="profile_department" class="input mt-1">
                            @error('department') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label" for="profile_password">New password</label>
                            <input wire:model="password" id="profile_password" type="password" class="input mt-1" autocomplete="new-password">
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="profile_password_confirmation">Confirm password</label>
                            <input wire:model="password_confirmation" id="profile_password_confirmation" type="password" class="input mt-1" autocomplete="new-password">
                        </div>
                    </div>

                    <p class="text-xs text-zinc-500">Leave password empty to keep the current password.</p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="closeProfileModal" type="button" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-success">Save profile</button>
                </div>
            </form>
        </div>
    @endif
</section>
