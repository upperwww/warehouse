<?php

use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('warehouse.dashboard'));

Route::post('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'sk'], true), 404);

    session(['locale' => $locale]);

    return back();
})->name('language.switch');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
