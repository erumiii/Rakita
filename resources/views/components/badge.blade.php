@props(['status'])

@php($map = [
    'isAvailable' => 'border-line-input bg-white text-ink',
    'isBorrowed' => 'bg-sidebar text-ink',
    'borrowed' => 'border-line-input bg-white text-ink',
    'overdue' => 'bg-ink text-white',
    'returned' => 'bg-sidebar text-ink',
    'pending' => 'border-line-input bg-white text-ink',
    'ready' => 'bg-ink text-white',
    'fulfilled' => 'bg-sidebar text-ink',
    'cancelled' => 'bg-sidebar text-ink-secondary',
    'expired' => 'bg-sidebar text-ink-secondary',
])
@php($labels = [
    'isAvailable' => 'Available',
    'isBorrowed' => 'Borrowed',
    'borrowed' => 'Borrowed',
    'overdue' => 'Overdue',
    'returned' => 'Returned',
    'pending' => 'Pending',
    'ready' => 'Ready for pickup',
    'fulfilled' => 'Fulfilled',
    'cancelled' => 'Cancelled',
    'expired' => 'Expired',
])
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-3 py-0.5 text-hint '.($map[$status] ?? 'border-line-input bg-white text-ink')]) }}
      data-status="{{ $status }}">{{ $labels[$status] ?? $status }}</span>
