@props(['href' => '#', 'icon' => null, 'active' => false, 'as' => 'a', 'danger' => false])

@php
$base = 'group flex h-8 w-full items-center gap-3 rounded-lg border px-4 text-base '
    .'transition-[background-color,border-color,box-shadow,color] duration-200 ease-out ';
$base .= $active
    ? 'border-line-editor bg-white shadow-nav '
    : ($danger
        ? 'border-transparent text-red-600 hover:bg-red-50 '
        : 'border-transparent text-ink hover:bg-white/60');
$iconClass = 'size-4 transition-colors duration-200 ease-out ';
$iconClass .= $active
    ? 'text-ink'
    : ($danger
        ? 'text-red-600'
        : 'text-ink-icon group-hover:text-ink');
@endphp

@if($as === 'button')
    <button {{ $attributes->merge(['class' => $base]) }}>
        @if($icon)<x-icon :name="$icon" :class="$iconClass" />@endif
        {{ $slot }}
    </button>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>
        @if($icon)<x-icon :name="$icon" :class="$iconClass" />@endif
        {{ $slot }}
    </a>
@endif
