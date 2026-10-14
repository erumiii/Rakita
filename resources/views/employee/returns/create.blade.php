@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Buku', 'icon' => '📚'],
    ['href' => '/employee/borrowings/create', 'label' => 'Peminjaman', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Pengembalian', 'icon' => '↩️', 'active' => true],
    ['href' => '/employee/borrowings/history', 'label' => 'Riwayat', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Pengembalian" subtitle="Catat pengembalian buku" :nav="$nav">
    <form method="POST" action="{{ route('employee.returns.store') }}">
        @csrf
        <table class="w-full border-collapse text-base">
            <thead>
                <tr class="border-b border-line text-left text-group text-ink-muted">
                    <th class="py-3 pr-4 font-medium"></th>
                    <th class="py-3 pr-4 font-medium">Peminjam</th>
                    <th class="py-3 pr-4 font-medium">Buku</th>
                    <th class="py-3 pr-4 font-medium">Tgl Pinjam</th>
                    <th class="py-3 pr-4 font-medium">Tgl Jatuh Tempo</th>
                    <th class="py-3 font-medium">Pengingat</th>
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
                    <tr><td colspan="6" class="py-6 text-center text-ink-secondary">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-8 flex items-end gap-3">
            <div>
                <label for="returned_at" class="mb-1 block text-group text-ink-muted">Tgl Kembali</label>
                <input type="datetime-local" name="returned_at" id="returned_at" value="{{ old('returned_at', now()->format('Y-m-d\TH:i')) }}"
                       class="h-10 rounded-lg border border-line px-3">
            </div>
            <button type="submit" class="h-10 rounded-lg bg-ink px-4 text-base text-white hover:opacity-90">Catat pengembalian</button>
        </div>
    </form>
</x-layout>
