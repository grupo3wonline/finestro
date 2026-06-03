<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="space-y-4">
    @csrf
    @method('patch')

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome</label>
        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2">
                <p class="text-sm text-yellow-600 dark:text-yellow-400">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Seu e-mail não está verificado.
                    <button form="send-verification" class="underline text-primary-600 hover:text-primary-700 font-medium">
                        Reenviar verificação
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-1 text-sm text-green-600 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i> Link de verificação enviado!
                    </p>
                @endif
            </div>
        @endif
    </div>

    <div class="flex items-center gap-4">
        <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fas fa-save mr-1"></i> Salvar
        </button>

        @if (session('status') === 'profile-updated')
            <p class="text-sm text-green-600 dark:text-green-400"><i class="fas fa-check mr-1"></i> Salvo!</p>
        @endif
    </div>
</form>
