<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Warehouse') }}</title>
    @vite(['resources/css/app.css'])
    @livewireStyles
    @wireUiStyles
</head>
<body>
    <div
        x-data="{ toast: '' }"
        x-on:notify.window="toast = $event.detail.message; setTimeout(() => toast = '', 2600)"
        class="min-h-screen"
    >
        <header class="border-b border-zinc-200 bg-white">
            <div class="page-shell flex items-center justify-between py-4">
                <div class="flex items-center gap-8">
                    <a href="{{ route('warehouse.dashboard') }}" class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-md bg-[#EB9800] font-black text-white">W</span>
                        <span class="text-lg font-bold text-[#333333]">Warehouse</span>
                    </a>

                    <nav class="hidden items-center gap-1 md:flex">
                        <a href="{{ route('warehouse.dashboard') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('warehouse.dashboard')])>{{ __('Dashboard') }}</a>
                        <a href="{{ route('warehouse.slabs') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('warehouse.slabs')])>{{ __('Items') }}</a>
                        <a href="{{ route('warehouse.inventory') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('warehouse.inventory')])>{{ __('Inventory') }}</a>
                        @if (auth()->user()?->canManageWarehouse())
                            <a href="{{ route('warehouse.materials') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('warehouse.materials')])>{{ __('Materials') }}</a>
                            <a href="{{ route('warehouse.item-flow') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('warehouse.item-flow')])>{{ __('Item flow') }}</a>
                            <a href="{{ route('warehouse.stock-movements') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('warehouse.stock-movements')])>{{ __('Stock movements') }}</a>
                        @endif
                        @if (auth()->user()?->isAdmin())
                            <a href="{{ route('warehouse.employees') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('warehouse.employees')])>{{ __('Employees') }}</a>
                        @endif
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex overflow-hidden rounded-md border border-zinc-300 bg-white text-xs font-bold">
                        <form method="POST" action="{{ route('language.switch', 'en') }}">
                            @csrf
                            <button type="submit" @class([
                                'px-2.5 py-2 transition hover:bg-zinc-50',
                                'bg-[#FDD07D] text-[#333333]' => app()->getLocale() === 'en',
                                'text-zinc-500' => app()->getLocale() !== 'en',
                            ]) aria-label="{{ __('English') }}">EN</button>
                        </form>
                        <form method="POST" action="{{ route('language.switch', 'sk') }}">
                            @csrf
                            <button type="submit" @class([
                                'border-l border-zinc-300 px-2.5 py-2 transition hover:bg-zinc-50',
                                'bg-[#FDD07D] text-[#333333]' => app()->getLocale() === 'sk',
                                'text-zinc-500' => app()->getLocale() !== 'sk',
                            ]) aria-label="{{ __('Slovak') }}">SK</button>
                        </form>
                    </div>

                    <a href="{{ route('warehouse.profile') }}" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-semibold text-[#333333] hover:bg-zinc-100">
                        <span class="grid size-9 place-items-center overflow-hidden rounded-full bg-[#FDD07D] text-xs font-bold text-[#333333] ring-1 ring-zinc-200">
                            @if (auth()->user()?->avatar_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url(auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}" class="size-full object-cover">
                            @else
                                {{ str(auth()->user()?->name ?? 'U')->substr(0, 2)->upper() }}
                            @endif
                        </span>
                        <span class="hidden lg:inline">{{ __('Profile') }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-secondary" type="submit">{{ __('Log out') }}</button>
                    </form>
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <div
            x-cloak
            x-show="toast"
            x-transition
            class="fixed bottom-5 right-5 rounded-md bg-[#333333] px-4 py-3 text-sm font-semibold text-white shadow-lg"
            x-text="toast"
        ></div>
    </div>

    @wireUiScripts
    @livewireScripts
</body>
</html>
