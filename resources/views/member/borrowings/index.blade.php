@php
$nav = [
    ['href' => '/member/books', 'label' => 'Book Collection', 'icon' => 'books'],
    ['href' => '/member/borrowings', 'label' => 'My Borrowings', 'icon' => 'book-open', 'active' => true],
];
@endphp

<x-layout title="My Borrowings" subtitle="Your current and past borrowings" :nav="$nav">
    <h2 class="mb-4 text-section text-ink">Currently Borrowed</h2>
    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Book</th>
                <th class="py-3 px-4 font-medium">Period</th>
                <th class="py-3 px-4 font-medium">Borrowed At</th>
                <th class="py-3 px-4 font-medium">Due At</th>
                <th class="py-3 px-4 font-medium">Reminder</th>
                <th class="py-3 px-4 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($active as $borrowing)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">{{ $borrowing->book->title }} — {{ $borrowing->book->author }} @if($borrowing->book->trashed()) <span class="text-ink-secondary">(deleted)</span> @endif</td>
                    <td class="py-3 px-4">{{ $borrowing->borrow_period_days }} days</td>
                    <td class="py-3 px-4">{{ format_dt($borrowing->borrowed_at) }}</td>
                    <td class="py-3 px-4">{{ format_dt($borrowing->due_at) }}</td>
                    <td class="py-3 px-4">{{ borrow_reminder($borrowing->due_at) }}</td>
                    <td class="py-3 px-4"><x-badge :status="$borrowing->status" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-ink-secondary">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="mb-4 mt-10 text-section text-ink">Returned</h2>
    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Book</th>
                <th class="py-3 px-4 font-medium">Borrowed At</th>
                <th class="py-3 px-4 font-medium">Returned At</th>
                <th class="py-3 px-4 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($returned as $borrowing)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">{{ $borrowing->book->title }} — {{ $borrowing->book->author }} @if($borrowing->book->trashed()) <span class="text-ink-secondary">(deleted)</span> @endif</td>
                    <td class="py-3 px-4">{{ format_dt($borrowing->borrowed_at) }}</td>
                    <td class="py-3 px-4">{{ format_dt($borrowing->returned_at) }}</td>
                    <td class="py-3 px-4"><x-badge :status="$borrowing->status" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-6 text-center text-ink-secondary">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-layout>
