<?php

namespace App\Bundles\Warehouse\Livewire;

use App\Bundles\Warehouse\Utils\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Employees extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'Worker';
    public bool $is_active = true;
    public string $phone = '';
    public string $position = '';
    public string $department = '';
    public string $search = '';
    public string $roleFilter = '';
    public bool $showModal = false;

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
            'phone' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
        ]);

        $password = $data['password'] ?? null;
        unset($data['password']);

        if ($password) {
            $data['password'] = $password;
        }

        $user = User::updateOrCreate(['id' => $this->editingId], $data);
        $user->syncRoles([$this->role]);

        $this->resetForm();
        $this->showModal = false;
        $this->dispatch('notify', message: 'Employee saved.');
    }

    public function edit(User $user): void
    {
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? UserRole::Worker->value;
        $this->is_active = $user->is_active;
        $this->phone = (string) $user->phone;
        $this->position = (string) $user->position;
        $this->department = (string) $user->department;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showModal = true;
    }

    public function delete(User $user): void
    {
        if ($user->is(Auth::user())) {
            $this->dispatch('notify', message: 'You cannot delete your own account.');
            return;
        }

        $user->delete();
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'name',
            'email',
            'password',
            'password_confirmation',
            'phone',
            'position',
            'department',
        ]);

        $this->role = UserRole::Worker->value;
        $this->is_active = true;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $roleCounts = collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role) => [$role->value => User::role($role->value)->count()]);

        return view('Warehouse::Livewire.employees', [
            'employees' => User::query()
                ->with('roles')
                ->when($this->search, function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    });
                })
                ->when($this->roleFilter, fn ($query) => $query->role($this->roleFilter))
                ->latest()
                ->paginate(10),
            'roles' => UserRole::options(),
            'roleCounts' => $roleCounts,
        ]);
    }
}
