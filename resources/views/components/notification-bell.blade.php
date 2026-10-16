@php
    $notifications = App\Models\Notification::query()->orderByDesc('created_at')->orderByDesc('id')->limit(8)->get();
    $unread = App\Models\Notification::query()->unread()->count();
@endphp

<div class="relative group" data-notification>
    <button type="button" data-notification-toggle aria-label="Notifications"
            class="flex size-10 items-center justify-center rounded-lg text-ink transition-colors duration-150 ease-out border border-transparent hover:bg-black/5 group-data-open:border-line group-data-open:bg-black/5">
        <x-icon name="bell" class="size-5" />
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
