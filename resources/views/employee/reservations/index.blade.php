@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => 'books'],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow', 'icon' => 'book-open'],
    ['href' => '/employee/reservations', 'label' => 'Reservations', 'icon' => 'clock', 'active' => true],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => 'users'],
];
@endphp

<x-layout title="Reservations" subtitle="Member book reservation queue" :nav="$nav">
    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Member</th>
                <th class="py-3 px-4 font-medium">Book</th>
                <th class="py-3 px-4 font-medium">Reserved At</th>
                <th class="py-3 px-4 font-medium">Pickup Deadline</th>
                <th class="py-3 px-4 font-medium">Status</th>
                <th class="py-3 px-4 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reservations as $reservation)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">{{ $reservation->member->name }}</td>
                    <td class="py-3 px-4">{{ $reservation->book->title }} — {{ $reservation->book->author }} @if($reservation->book->trashed()) <span class="text-ink-secondary">(deleted)</span> @endif</td>
                    <td class="py-3 px-4">{{ format_dt($reservation->created_at) }}</td>
                    <td class="py-3 px-4">{{ format_dt($reservation->expires_at) }}</td>
                    <td class="py-3 px-4"><x-badge :status="$reservation->status" /></td>
                    <td class="py-3 px-4">
                        @if ($reservation->status === 'pending')
                            <form method="POST" action="{{ route('employee.reservations.destroy', $reservation) }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" data-confirm="Cancel {{ $reservation->member->name }}'s reservation for &quot;{{ $reservation->book->title }}&quot;?" data-confirm-title="Cancel Reservation" data-confirm-action="Yes, cancel"
                                        class="inline-flex h-8 items-center rounded-md border border-line-editor bg-white px-3 text-hint text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">Cancel</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-ink-secondary">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $reservations->links() }}
</x-layout>
