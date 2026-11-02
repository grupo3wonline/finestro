@auth
@php $passwordUser = Auth::user(); @endphp

{{-- Password aging: reminder during the last days (default 7) before the password expires --}}
@if($passwordUser->isPasswordExpiringSoon())
@php
    $passwordDays = $passwordUser->passwordDaysRemaining();
    $passwordWhen = $passwordDays === 0 ? 'hoje' : ($passwordDays === 1 ? 'em 1 dia' : "em {$passwordDays} dias");
@endphp
<div x-data="{ open: true }" x-show="open" x-cloak role="status"
    class="bg-surface-900 dark:bg-surface-800 text-white px-4 py-2.5 text-sm">
    <div class="max-w-7xl mx-auto flex items-center justify-center gap-3 text-center">
        <p>
            <i class="fas fa-key mr-1 text-accent-300" aria-hidden="true"></i>
            <strong>Sua senha expira {{ $passwordWhen }}</strong> ({{ $passwordUser->passwordExpiresAt()->format('d/m/Y') }}).
            Troque agora para não perder o acesso à sua conta.
            <a href="{{ route('settings.index') }}#alterar-senha" class="underline font-bold ml-1 text-accent-300 hover:text-accent-200">Trocar senha →</a>
        </p>
        <button type="button" @click="open = false" class="shrink-0 p-1 text-surface-300 hover:text-white" aria-label="Fechar lembrete">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
</div>
@endif
@endauth
