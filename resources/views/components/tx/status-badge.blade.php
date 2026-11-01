@props(['transaction'])

@php
    $classes = match (true) {
        $transaction->is_overdue => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        $transaction->isPending() => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    };
    $icon = match (true) {
        $transaction->is_overdue => 'fa-triangle-exclamation',
        $transaction->isPending() => 'fa-clock',
        default => 'fa-check',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {$classes}"]) }}
    @if($transaction->isPending()) title="Vence em {{ $transaction->effective_due_date->format('d/m/Y') }}" @endif>
    <i class="fas {{ $icon }} mr-1 text-[10px]"></i>{{ $transaction->status_label }}
</span>
