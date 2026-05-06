<section class="w-full max-w-md">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 grid size-14 place-items-center rounded-lg bg-[#EB9800] text-2xl font-black text-white">W</div>
        <h1 class="text-3xl font-bold text-[#333333]">{{ __('Warehouse login') }}</h1>
    </div>

    <form wire:submit="login" class="panel space-y-5 p-6">
        <div>
            <label class="label" for="email">{{ __('Email') }}</label>
            <input wire:model="email" id="email" type="email" class="input mt-1" autocomplete="email" autofocus>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="password">{{ __('Password') }}</label>
            <input wire:model="password" id="password" type="password" class="input mt-1" autocomplete="current-password">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-zinc-600">
            <input wire:model="remember" type="checkbox" class="rounded border-zinc-300 text-[#EB9800] focus:ring-[#EB9800]">
            {{ __('Remember me') }}
        </label>

        <button type="submit" class="btn-primary w-full">{{ __('Log in') }}</button>
    </form>
</section>
