@php
    $notifications = App\Models\Notification::query()->orderByDesc('created_at')->orderByDesc('id')->limit(8)->get();
    $unread = App\Models\Notification::query()->unread()->count();
@endphp

<div class="relative" data-notification>
    <button type="button" data-notification-toggle aria-label="Notifications"
            class="flex size-10 items-center justify-center rounded-lg text-ink hover:bg-black/5">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c.316.316.72.599 1.21.813a23.848 23.848 0 0 0 5.454 1.31m0 0a8.966 8.966 0 0 1-2.56 1.31m2.56-1.31a8.966 8.966 0 0 0 2.56 1.31m0 0a2.25 2.25 0 1 0 3.5-1.87c.017.017.033.034.05.05Z" />
        </svg>
        @if ($unread > 0)
            <span data-notification-count
                  class="absolute -right-0.5 -top-0.5 flex size-5 items-center justify-center rounded-full bg-brand text-xs font-medium text-white">
                {{ $unread }}
            </span>
        @endif
    </button>

    <div data-notification-panel class="absolute right-0 top-12 z-50 hidden w-96 rounded-xl border border-line bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <p class="text-sm font-medium">Notifications</p>
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button type="submit" class="text-xs text-ink-secondary hover:text-ink">Mark all read</button>
            </form>
        </div>
        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $n)
                <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-start gap-3 border-b border-line px-4 py-3 text-left last:border-b-0 {{ $n->read_at ? '' : 'bg-black/[0.03]' }} hover:bg-black/5">
                        @unless ($n->read_at)
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand"></span>
                        @endunless
                        <span class="text-sm {{ $n->read_at ? 'text-ink-secondary' : 'text-ink' }}">{{ $n->label() }}</span>
                    </button>
                </form>
            @empty
                <p class="px-4 py-6 text-sm text-ink-secondary">No notifications yet.</p>
            @endforelse
        </div>
    </div>
</div>
