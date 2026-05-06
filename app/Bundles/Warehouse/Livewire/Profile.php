<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Utils\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Profile extends Component
{
    use WithFileUploads;

    public ?int $profileUserId = null;
    public $photo = null;
    public bool $showProfileModal = false;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $position = '';
    public string $department = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(?User $user = null): void
    {
        if ($user && ! Auth::user()?->hasRole(UserRole::Admin->value)) {
            abort(403);
        }

        $profileUser = $user ?: Auth::user();
        abort_unless($profileUser, 403);

        $this->profileUserId = $profileUser->id;
        $this->fillProfileForm();
    }

    public function editProfile(): void
    {
        abort_unless($this->canEditOwnProfile(), 403);

        $this->fillProfileForm();
        $this->showProfileModal = true;
    }

    public function saveProfile(): void
    {
        abort_unless($this->canEditOwnProfile(), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->profileUserId)],
            'phone' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! $data['password']) {
            unset($data['password']);
        }

        $this->profileUser()->update($data);
        $this->fillProfileForm();
        $this->showProfileModal = false;
        $this->dispatch('notify', message: 'Profile updated.');
    }

    public function closeProfileModal(): void
    {
        $this->fillProfileForm();
        $this->showProfileModal = false;
    }

    public function savePhoto(): void
    {
        abort_unless($this->canManagePhoto(), 403);

        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->deleteStoredPhoto();

        $this->profileUser()->update([
            'avatar_path' => $this->photo->store('avatars', 'public'),
        ]);

        $this->photo = null;
        $this->dispatch('notify', message: 'Profile photo updated.');
    }

    public function deletePhoto(): void
    {
        abort_unless($this->canManagePhoto(), 403);

        $this->deleteStoredPhoto();
        $this->profileUser()->update(['avatar_path' => null]);
        $this->dispatch('notify', message: 'Profile photo removed.');
    }

    public function canManagePhoto(): bool
    {
        return $this->profileUser()->is(Auth::user());
    }

    public function canEditOwnProfile(): bool
    {
        return $this->profileUser()->is(Auth::user())
            && Auth::user()?->hasRole(UserRole::Admin->value);
    }

    private function fillProfileForm(): void
    {
        $profileUser = $this->profileUser();

        $this->name = (string) $profileUser->name;
        $this->email = (string) $profileUser->email;
        $this->phone = (string) $profileUser->phone;
        $this->position = (string) $profileUser->position;
        $this->department = (string) $profileUser->department;
        $this->password = '';
        $this->password_confirmation = '';
    }

    private function deleteStoredPhoto(): void
    {
        $profileUser = $this->profileUser();

        if (! $profileUser->avatar_path) {
            return;
        }

        Storage::disk('public')->delete($profileUser->avatar_path);
    }

    private function profileUser(): User
    {
        return User::query()
            ->with('roles')
            ->findOrFail($this->profileUserId ?? Auth::id());
    }

    public function render()
    {
        $profileUser = $this->profileUser();

        $roleName = $profileUser->roles->first()?->name ?? UserRole::Worker->value;
        $role = UserRole::tryFrom($roleName) ?? UserRole::Worker;

        return view('Warehouse::Livewire.profile', [
            'profileUser' => $profileUser,
            'roleName' => $roleName,
            'roleBadge' => $role->badge(),
            'canManagePhoto' => $this->canManagePhoto(),
            'canEditOwnProfile' => $this->canEditOwnProfile(),
        ]);
    }
}
