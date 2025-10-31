<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Mohon untuk memverifikasi akun anda melalui email yang kami kirim sebelum mengakses Dashboard. Jika anda tidak menerima email verifikasi, tekan tombol di bawah untuk mengirim ulang email verifikasi.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('Link verifikasi telah dikirim ke alamat email anda.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="text-white">
                    {{ __('Kirim Email Verifikasi') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
