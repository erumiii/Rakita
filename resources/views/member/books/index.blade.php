@php
$nav = [
    ['href' => '/member/books', 'label' => 'Book Collection', 'icon' => '📚', 'active' => true],
    ['href' => '/member/borrowings', 'label' => 'My Borrowed Books', 'icon' => '📖'],
];
@endphp

<x-layout title="Book Collection" subtitle="Koleksi buku perpustakaan" :nav="$nav">
    <form method="GET" action="{{ route('member.books.index') }}" class="mb-6 flex items-end gap-3">
        <x-input name="q" label="Search" placeholder="Title, author, or ISBN" :value="request('q')" />
        <button type="submit"
                class="h-10 rounded-lg bg-brand px-4 text-base text-white hover:opacity-90">Search</button>
    </form>

    <table class="w-full border-collapse text-base">
        <thead>
            <tr class="border-b border-line text-left">
                <th class="py-3 pr-4 font-medium">Title</th>
                <th class="py-3 pr-4 font-medium">Author</th>
                <th class="py-3 pr-4 font-medium">Publisher</th>
                <th class="py-3 pr-4 font-medium">Page</th>
                <th class="py-3 pr-4 font-medium">ISBN</th>
                <th class="py-3 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($books as $book)
                <tr class="border-b border-line">
                    <td class="py-3 pr-4">
                        <a href="{{ route('member.books.show', $book) }}" class="hover:text-ink-secondary">{{ $book->title }}</a>
                    </td>
                    <td class="py-3 pr-4">{{ $book->author }}</td>
                    <td class="py-3 pr-4">{{ $book->publisher }}</td>
                    <td class="py-3 pr-4">{{ $book->page }}</td>
                    <td class="py-3 pr-4">{{ $book->isbn }}</td>
                    <td class="py-3"><x-badge :status="$book->status" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-ink-secondary">No books found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $books->links() }}
</x-layout>
