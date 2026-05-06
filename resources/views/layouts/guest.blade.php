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
    <div class="fixed right-4 top-4 flex overflow-hidden rounded-md border border-zinc-300 bg-white text-xs font-bold shadow-sm">
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

    <main class="grid min-h-screen place-items-center bg-[#F7F6F1] px-4">
        {{ $slot }}
    </main>

    @wireUiScripts
    @livewireScripts
</body>
</html>
