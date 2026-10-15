@php
$nav = [
    ['href' => '/member/books', 'label' => 'Book Collection', 'icon' => 'books'],
    ['href' => '/member/borrowings', 'label' => 'My Borrowings', 'icon' => 'book-open'],
    ['href' => '/member/reservations', 'label' => 'My Reservations', 'icon' => 'clock', 'active' => true],
];
@endphp

<x-layout title="My Reservations" subtitle="Your book reservation queue" :nav="$nav">
    <h2 class="mb-4 text-section text-ink">Pending</h2>
    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Book</th>
                <th class="py-3 px-4 font-medium">Reserved At</th>
                <th class="py-3 px-4 font-medium">Queue</th>
                <th class="py-3 px-4 font-medium">Pickup Deadline</th>
                <th class="py-3 px-4 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pending as $reservation)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">{{ $reservation->book->title }} — {{ $reservation->book->author }}</td>
                    <td class="py-3 px-4">{{ format_dt($reservation->created_at) }}</td>
                    <td class="py-3 px-4">
                        @if ($reservation->expires_at)
                            <x-badge status="ready" />
                        @else
                            #{{ $positions[$reservation->id] }} in queue
                        @endif
                    </td>
                    <td class="py-3 px-4">{{ format_dt($reservation->expires_at) }}</td>
                    <td class="py-3 px-4">
                        <form method="POST" action="{{ route('member.reservations.destroy', $reservation) }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" data-confirm="Cancel this reservation?"
                                    class="inline-flex h-8 items-center rounded-md border border-line-editor bg-white px-3 text-hint text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">Cancel</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-ink-secondary">No pending reservations.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="mb-4 mt-10 text-section text-ink">History</h2>
    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Book</th>
                <th class="py-3 px-4 font-medium">Reserved At</th>
                <th class="py-3 px-4 font-medium">Updated At</th>
                <th class="py-3 px-4 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($history as $reservation)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">{{ $reservation->book->title }} — {{ $reservation->book->author }}</td>
                    <td class="py-3 px-4">{{ format_dt($reservation->created_at) }}</td>
                    <td class="py-3 px-4">{{ format_dt($reservation->updated_at) }}</td>
                    <td class="py-3 px-4"><x-badge :status="$reservation->status" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-6 text-center text-ink-secondary">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-layout>
