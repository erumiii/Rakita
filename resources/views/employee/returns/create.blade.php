@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => '📚'],
    ['href' => '/employee/borrowings/create', 'label' => 'Borrow Book', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Return Book', 'icon' => '↩️', 'active' => true],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow History', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Return Book" subtitle="Catat pengembalian buku" :nav="$nav">
    <form method="POST" action="{{ route('employee.returns.store') }}">
        @csrf
        @if (session('success'))
            <p class="mb-4 rounded-lg border border-line-input bg-white px-4 py-2 text-base text-ink">{{ session('success') }}</p>
        @endif
        <table class="w-full border-collapse text-base">
            <thead>
                <tr class="border-b border-line text-left">
                    <th class="py-3 pr-4 font-medium"></th>
                    <th class="py-3 pr-4 font-medium">Borrower</th>
                    <th class="py-3 pr-4 font-medium">Book</th>
                    <th class="py-3 pr-4 font-medium">Borrowed At</th>
                    <th class="py-3 pr-4 font-medium">Due At</th>
                    <th class="py-3 font-medium">Reminder</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($borrowings as $borrowing)
                    <tr class="border-b border-line">
                        <td class="py-3 pr-4">
                            <input type="radio" name="borrowing_id" value="{{ $borrowing->id }}" id="borrowing_{{ $borrowing->id }}" required>
                        </td>
                        <td class="py-3 pr-4">{{ $borrowing->member->name }}</td>
                        <td class="py-3 pr-4">{{ $borrowing->book->title }} — {{ $borrowing->book->author }}</td>
                        <td class="py-3 pr-4">{{ format_dt($borrowing->borrowed_at) }}</td>
                        <td class="py-3 pr-4">{{ format_dt($borrowing->due_at) }}</td>
                        <td class="py-3">{{ borrow_reminder($borrowing->due_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-ink-secondary">No active borrowings.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-8 flex gap-3">
            <button type="submit" class="h-10 rounded-lg bg-brand px-4 text-base text-white hover:opacity-90">Catat pengembalian</button>
        </div>
    </form>
</x-layout>
