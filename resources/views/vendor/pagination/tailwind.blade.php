@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="mt-6 flex items-center justify-between">

        {{-- Mobile: prev/next only --}}
        <div class="flex gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="btn inline-flex h-9 items-center rounded-lg border border-line-editor bg-white px-3.5 text-base text-ink-muted opacity-50"
                      aria-disabled="true">Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="btn inline-flex h-9 items-center rounded-lg border border-line-editor bg-white px-3.5 text-base text-ink">Prev</a>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="btn inline-flex h-9 items-center rounded-lg border border-line-editor bg-white px-3.5 text-base text-ink">Next</a>
            @else
                <span class="btn inline-flex h-9 items-center rounded-lg border border-line-editor bg-white px-3.5 text-base text-ink-muted opacity-50"
                      aria-disabled="true">Next</span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden w-full items-center justify-between sm:flex">
            <p class="text-hint text-ink-secondary">
                Showing
                @if ($paginator->firstItem())
                    <span class="font-medium text-ink">{{ $paginator->firstItem() }}</span>
                    to <span class="font-medium text-ink">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                of <span class="font-medium text-ink">{{ $paginator->total() }}</span> results
            </p>

            <div class="inline-flex items-center gap-2">
                {{-- Prev --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true"
                          class="btn-icon inline-flex size-10 items-center justify-center rounded-lg border border-line-editor bg-white text-ink-muted opacity-50">
                        <x-icon name="chevron-left" class="size-4" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"
                       class="btn-icon inline-flex size-10 items-center justify-center rounded-lg border border-line-editor bg-white text-ink">
                        <x-icon name="chevron-left" class="size-4" />
                    </a>
                @endif

                {{-- Pages --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex size-10 items-center justify-center text-base text-ink-muted">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="inline-flex size-10 items-center justify-center rounded-lg bg-ink text-base text-white shadow-nav">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Go to page {{ $page }}"
                                   class="btn-icon inline-flex size-10 items-center justify-center rounded-lg border border-line-editor bg-white text-base text-ink">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"
                       class="btn-icon inline-flex size-10 items-center justify-center rounded-lg border border-line-editor bg-white text-ink">
                        <x-icon name="chevron-right" class="size-4" />
                    </a>
                @else
                    <span aria-disabled="true"
                          class="btn-icon inline-flex size-10 items-center justify-center rounded-lg border border-line-editor bg-white text-ink-muted opacity-50">
                        <x-icon name="chevron-right" class="size-4" />
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
