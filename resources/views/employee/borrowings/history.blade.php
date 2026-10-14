@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => '📚'],
    ['href' => '/employee/borrowings/create', 'label' => 'Borrow Book', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Return Book', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow History', 'icon' => '🕘', 'active' => true],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Borrow History" subtitle="Riwayat peminjaman dan pengembalian" :nav="$nav">
    <table class="w-full border-collapse text-base">
        <thead>
            <tr class="border-b border-line text-left">
                <th class="py-3 pr-4 font-medium">Borrower</th>
                <th class="py-3 pr-4 font-medium">Book</th>
                <th class="py-3 pr-4 font-medium">Period</th>
                <th class="py-3 pr-4 font-medium">Borrowed At</th>
                <th class="py-3 pr-4 font-medium">Returned At</th>
                <th class="py-3 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($borrowings as $borrowing)
                <tr class="border-b border-line">
                    <td class="py-3 pr-4">{{ $borrowing->member->name }}</td>
                    <td class="py-3 pr-4">{{ $borrowing->book->title }} — {{ $borrowing->book->author }} — {{ $borrowing->book->publisher }}</td
                    >
                    <td class="py-3 pr-4">{{ $borrowing->borrow_period_days }} hari</td>
                    <td class="py-3 pr-4">{{ format_dt($borrowing->borrowed_at) }}</td>
                    <td class="py-3 pr-4">{{ format_dt($borrowing->returned_at) }}</td>
                    <td class="py-3"><x-badge :status="$borrowing->status" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-ink-secondary">No borrowings yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $borrowings->links() }}
</x-layout>
