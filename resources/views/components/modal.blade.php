@props(['name', 'title', 'method' => 'POST', 'action' => null, 'size' => 'md', 'open' => false])

@php
$maxW = $size === 'lg' ? 'max-w-2xl' : 'max-w-xl';
@endphp

<dialog id="modal-{{ $name }}"
        class="m-auto w-[92vw] {{ $maxW }} rounded-lg bg-white p-0 text-ink backdrop:bg-black/30 backdrop:backdrop-blur-[2px]"
        data-modal @if($open) data-open="1" @endif>
    <div data-modal-frame class="flex max-h-[85vh] flex-col">
        <div class="flex items-center justify-between border-b border-line px-6 py-4">
            <h2 class="text-section text-ink">{{ $title }}</h2>
            <button type="button" data-modal-close aria-label="Close"
                    class="flex size-7 items-center justify-center rounded-md text-ink-muted transition-colors duration-200 ease-out hover:bg-sidebar hover:text-ink">
                <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M6.3 6.3a1 1 0 0 1 1.4 0L12 10.6l4.3-4.3a1 1 0 1 1 1.4 1.4L13.4 12l4.3 4.3a1 1 0 0 1-1.4 1.4L12 13.4l-4.3 4.3a1 1 0 0 1-1.4-1.4L10.6 12 6.3 7.7a1 1 0 0 1 0-1.4Z"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto px-6 py-5">
            {{ $slot }}
        </div>
    </div>
</dialog>
