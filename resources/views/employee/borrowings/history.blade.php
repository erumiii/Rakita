@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Buku', 'icon' => '📚'],
    ['href' => '/employee/borrowings/create', 'label' => 'Peminjaman', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Pengembalian', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Riwayat', 'icon' => '🕘', 'active' => true],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Riwayat" subtitle="Riwayat peminjaman dan pengembalian" :nav="$nav">
    <table class="w-full border-collapse text-base">
        <thead>
            <tr class="border-b border-line text-left text-group text-ink-muted">
                <th class="py-3 pr-4 font-medium">Peminjam</th>
                <th class="py-3 pr-4 font-medium">Buku</th>
                <th class="py-3 pr-4 font-medium">Periode</th>
                <th class="py-3 pr-4 font-medium">Tgl Pinjam</th>
                <th class="py-3 pr-4 font-medium">Tgl Kembali</th>
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
                <tr><td colspan="6" class="py-6 text-center text-ink-secondary">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $borrowings->links() }}
</x-layout>
