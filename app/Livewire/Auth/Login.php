<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login()
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $this->remember)) {
            $this->addError('email', 'These credentials do not match our records.');
            return;
        }

        if (! Auth::user()->is_active) {
            Auth::logout();
            $this->addError('email', 'This account is inactive.');
            return;
        }

        request()->session()->regenerate();

        return redirect()->intended(route('warehouse.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
