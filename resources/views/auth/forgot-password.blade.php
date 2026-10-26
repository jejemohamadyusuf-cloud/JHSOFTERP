<x-guest-layout>
    <h1 class="auth-title">Reset kata sandi</h1>
    <p class="auth-description">Masukkan email akun Anda. Kami akan mengirimkan tautan untuk membuat kata sandi baru.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="auth-link" href="{{ route('login') }}">Kembali ke masuk</a>
            <x-primary-button class="button primary auth-submit">
                Kirim tautan reset
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
