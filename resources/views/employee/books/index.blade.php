@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Buku', 'icon' => '📚', 'active' => true],
    ['href' => '/employee/borrowings/create', 'label' => 'Peminjaman', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Pengembalian', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Riwayat', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Buku" subtitle="Kelola koleksi buku perpustakaan" :nav="$nav">
    <div class="mb-6 flex items-end justify-between">
        <form method="GET" action="{{ route('employee.books.index') }}" class="flex items-end gap-3">
            <x-input name="q" label="Cari" placeholder="Judul, penulis, atau ISBN" :value="request('q')" />
            <div class="flex flex-col gap-2">
                <label for="status" class="text-base text-ink">Status</label>
                <select name="status" id="status"
                        class="h-10 rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                    <option value="">Semua</option>
                    <option value="isAvailable" @selected(request('status') === 'isAvailable')>isAvailable</option>
                    <option value="isBorrowed" @selected(request('status') === 'isBorrowed')>isBorrowed</option>
                </select>
            </div>
            <button type="submit"
                    class="h-10 rounded-lg bg-ink px-4 text-base text-white hover:opacity-90">Filter</button>
        </form>
        <a href="{{ route('employee.books.create') }}"
           class="inline-flex h-10 items-center rounded-lg bg-ink px-4 text-base text-white hover:opacity-90">+ Tambah
            Buku</a>
    </div>

    <table class="w-full border-collapse text-base">
        <thead>
            <tr class="border-b border-line text-left text-group text-ink-muted">
                <th class="py-3 pr-4 font-medium">Title</th>
                <th class="py-3 pr-4 font-medium">Author</th>
                <th class="py-3 pr-4 font-medium">Publisher</th>
                <th class="py-3 pr-4 font-medium">Page</th>
                <th class="py-3 pr-4 font-medium">ISBN</th>
                <th class="py-3 pr-4 font-medium">Status</th>
                <th class="py-3 font-medium">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($books as $book)
                <tr class="border-b border-line">
                    <td class="py-3 pr-4">{{ $book->title }}</td>
                    <td class="py-3 pr-4">{{ $book->author }}</td>
                    <td class="py-3 pr-4">{{ $book->publisher }}</td>
                    <td class="py-3 pr-4">{{ $book->page }}</td>
                    <td class="py-3 pr-4">{{ $book->isbn }}</td>
                    <td class="py-3 pr-4"><x-badge :status="$book->status" /></td>
                    <td class="py-3">
                        <a href="{{ route('employee.books.edit', $book) }}" class="text-ink-secondary hover:text-ink">Edit</a>
                        <form method="POST" action="{{ route('employee.books.destroy', $book) }}" class="inline"
                              onsubmit="return confirm('Hapus buku ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-ink-secondary hover:text-ink">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-ink-secondary">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $books->links() }}
</x-layout>
