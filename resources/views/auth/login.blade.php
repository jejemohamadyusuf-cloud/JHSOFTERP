<x-guest-layout>
    <h1 class="auth-title">Masuk ke JHSOFTERP</h1>
    <p class="auth-description">Masuk untuk melanjutkan ke sistem ERP.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Kata sandi" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Ingat saya</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="auth-link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
            <x-primary-button class="button primary auth-submit">
                Masuk
            </x-primary-button>
        </div>
    </form>

    <p class="auth-footer">Belum punya akun? <a class="auth-link" href="{{ route('register') }}">Daftar</a></p>
</x-guest-layout>
