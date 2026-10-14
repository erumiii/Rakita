@props(['href' => '#', 'icon' => null, 'active' => false, 'as' => 'a'])

@if($as === 'button')
    <button {{ $attributes->merge(['class' => 'flex h-8 w-full items-center gap-3 rounded-lg border px-4 text-base text-ink '.($active ? 'border-line-editor bg-white shadow-nav' : 'border-transparent')]) }}>
        <span class="{{ $active ? 'text-ink' : 'text-ink-icon' }}">{{ $icon }}</span>
        {{ $slot }}
    </button>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex h-8 items-center gap-3 rounded-lg border px-4 text-base text-ink '.($active ? 'border-line-editor bg-white shadow-nav' : 'border-transparent')]) }}>
        <span class="{{ $active ? 'text-ink' : 'text-ink-icon' }}">{{ $icon }}</span>
        {{ $slot }}
    </a>
@endif
