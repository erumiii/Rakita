@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Buku', 'icon' => '📚', 'active' => true],
    ['href' => '/employee/borrowings/create', 'label' => 'Peminjaman', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Pengembalian', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Riwayat', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Tambah Buku" subtitle="Tambah buku baru ke koleksi" :nav="$nav">
    <form method="POST" action="{{ route('employee.books.store') }}" class="max-w-3xl">
        @csrf
        <x-section heading="Informasi Buku">
            <x-input name="title" label="Title" />
            <x-input name="author" label="Author" />
            <x-input name="publisher" label="Publisher" />
            <x-input name="page" label="Page" type="number" />
            <x-input name="isbn" label="ISBN" />
        </x-section>
        <div class="mt-8 flex gap-3">
            <button type="submit" class="h-10 rounded-lg bg-ink px-4 text-base text-white hover:opacity-90">Simpan</button>
            <a href="{{ route('employee.books.index') }}"
               class="inline-flex h-10 items-center rounded-lg border border-line-input bg-white px-4 text-base text-ink">Batal</a>
        </div>
    </form>
</x-layout>
