@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Buku', 'icon' => '📚'],
    ['href' => '/employee/borrowings/create', 'label' => 'Peminjaman', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Pengembalian', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Riwayat', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥', 'active' => true],
];
@endphp

<x-layout title="Members" subtitle="Kelola akun anggota perpustakaan" :nav="$nav">
    <table class="w-full border-collapse text-base">
        <thead>
            <tr class="border-b border-line text-left text-group text-ink-muted">
                <th class="py-3 pr-4 font-medium">Nama</th>
                <th class="py-3 pr-4 font-medium">Username</th>
                <th class="py-3 pr-4 font-medium">Status</th>
                <th class="py-3 pr-4 font-medium">Pinjaman aktif</th>
                <th class="py-3 font-medium">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($members as $member)
                <tr class="border-b border-line">
                    <td class="py-3 pr-4">{{ $member->name }}</td>
                    <td class="py-3 pr-4">{{ $member->username }}</td>
                    <td class="py-3 pr-4">
                        @if ($member->is_active)
                            <span class="inline-flex items-center rounded-full border border-line-input px-3 py-0.5 text-hint text-ink">aktif</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-ink px-3 py-0.5 text-hint text-white">nonaktif</span>
                        @endif
                    </td>
                    <td class="py-3 pr-4">{{ $member->active_borrowings_count }}</td>
                    <td class="py-3"><a href="{{ route('employee.members.edit', $member) }}" class="text-ink-secondary hover:text-ink">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-ink-secondary">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $members->links() }}
</x-layout>
