@php $text = $message ?? session('success'); @endphp

@if ($text)
    <div role="status"
         data-alert
         class="alert-enter fixed right-6 top-6 z-50 flex max-w-[420px] items-start gap-3 rounded-lg border border-line-editor bg-white px-4 py-3 shadow-nav">
        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-brand">
            <x-icon name="check" class="size-3.5 text-white" />
        </span>
        <p class="text-base text-ink">{{ $text }}</p>
        <button type="button" data-alert-close aria-label="Close"
                class="ml-2 mt-0.5 shrink-0 text-ink-muted transition-colors duration-200 ease-out hover:text-ink">
            <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M6.3 6.3a1 1 0 0 1 1.4 0L12 10.6l4.3-4.3a1 1 0 1 1 1.4 1.4L13.4 12l4.3 4.3a1 1 0 0 1-1.4 1.4L12 13.4l-4.3 4.3a1 1 0 0 1-1.4-1.4L10.6 12 6.3 7.7a1 1 0 0 1 0-1.4Z"/></svg>
        </button>
    </div>
    <script>
        (function () {
            var toast = document.currentScript.previousElementSibling;
            var hide = function () {
                toast.style.transition = 'opacity 200ms ease-in, transform 200ms ease-in';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-8px)';
                setTimeout(function () { toast.remove(); }, 220);
            };
            var timer = setTimeout(hide, 4000);
            toast.querySelector('[data-alert-close]').addEventListener('click', function () {
                clearTimeout(timer);
                hide();
            });
        })();
    </script>
@endif
