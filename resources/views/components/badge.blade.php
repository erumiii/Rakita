@props(['status'])

@php($map = [
    'isAvailable' => 'border-line-input bg-white text-ink',
    'isBorrowed' => 'bg-sidebar text-ink',
    'borrowed' => 'border-line-input bg-white text-ink',
    'overdue' => 'bg-ink text-white',
    'returned' => 'bg-sidebar text-ink',
])
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-3 py-0.5 text-hint '.$map[$status]]) }}
      data-status="{{ $status }}">{{ $status }}</span>
