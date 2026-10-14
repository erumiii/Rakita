@php
$nav = [
    ['href' => '/member/books', 'label' => 'Koleksi Buku', 'icon' => '📚'],
    ['href' => '/member/borrowings', 'label' => 'Pinjaman Saya', 'icon' => '📖', 'active' => true],
];
@endphp

<x-layout title="Pinjaman Saya" subtitle="Peminjaman Anda saat ini dan riwayat" :nav="$nav">
    <h2 class="mb-4 text-section text-ink">Sedang Dipinjam</h2>
    <table class="w-full border-collapse text-base">
        <thead>
            <tr class="border-b border-line text-left text-group text-ink-muted">
                <th class="py-3 pr-4 font-medium">Buku</th>
                <th class="py-3 pr-4 font-medium">Periode</th>
                <th class="py-3 pr-4 font-medium">Tgl Pinjam</th>
                <th class="py-3 pr-4 font-medium">Tgl Jatuh Tempo</th>
                <th class="py-3 pr-4 font-medium">Pengingat</th>
                <th class="py-3 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($active as $borrowing)
                <tr class="border-b border-line">
                    <td class="py-3 pr-4">{{ $borrowing->book->title }} — {{ $borrowing->book->author }} @if($borrowing->book->trashed()) <span class="text-ink-secondary">(dihapus)</span> @endif</td>
                    <td class="py-3 pr-4">{{ $borrowing->borrow_period_days }} hari</td>
                    <td class="py-3 pr-4">{{ format_dt($borrowing->borrowed_at) }}</td>
                    <td class="py-3 pr-4">{{ format_dt($borrowing->due_at) }}</td>
                    <td class="py-3">{{ borrow_reminder($borrowing->due_at) }}</td>
                    <td class="py-3"><x-badge :status="$borrowing->status" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-ink-secondary">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="mb-4 mt-10 text-section text-ink">Sudah Dikembalikan</h2>
    <table class="w-full border-collapse text-base">
        <thead>
            <tr class="border-b border-line text-left text-group text-ink-muted">
                <th class="py-3 pr-4 font-medium">Buku</th>
                <th class="py-3 pr-4 font-medium">Tgl Pinjam</th>
                <th class="py-3 pr-4 font-medium">Tgl Kembali</th>
                <th class="py-3 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($returned as $borrowing)
                <tr class="border-b border-line">
                    <td class="py-3 pr-4">{{ $borrowing->book->title }} — {{ $borrowing->book->author }} @if($borrowing->book->trashed()) <span class="text-ink-secondary">(dihapus)</span> @endif</td>
                    <td class="py-3 pr-4">{{ format_dt($borrowing->borrowed_at) }}</td>
                    <td class="py-3 pr-4">{{ format_dt($borrowing->returned_at) }}</td>
                    <td class="py-3"><x-badge :status="$borrowing->status" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-6 text-center text-ink-secondary">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-layout>
