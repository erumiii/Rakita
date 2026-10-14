@php
$nav = [
    ['href' => '/member/books', 'label' => 'Book Collection', 'icon' => 'books', 'active' => true],
    ['href' => '/member/borrowings', 'label' => 'My Borrowings', 'icon' => 'book-open'],
];
@endphp

<x-layout title="Book Collection" subtitle="The library's book collection" :nav="$nav">
    <form method="GET" action="{{ route('member.books.index') }}" class="mb-6 flex items-end gap-6">
        <div class="flex items-end gap-2">
            <x-input name="q" label="Search" placeholder="Title, author, or ISBN" :value="request('q')" />
            <button type="submit"
                    class="inline-flex h-10 items-center rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">Search</button>
        </div>
        <div class="flex flex-col gap-2">
            <label for="status" class="text-base text-ink">Status</label>
            <select name="status" id="status" onchange="this.form.submit()"
                    class="h-10 rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                <option value="">All</option>
                <option value="isAvailable" @selected(request('status') === 'isAvailable')>Available</option>
                <option value="isBorrowed" @selected(request('status') === 'isBorrowed')>Borrowed</option>
            </select>

        </div>

    </form>

    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Title</th>
                <th class="py-3 px-4 font-medium">Author</th>
                <th class="py-3 px-4 font-medium">Publisher</th>
                <th class="py-3 px-4 font-medium">Pages</th>
                <th class="py-3 px-4 font-medium">ISBN</th>
                <th class="py-3 px-4 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($books as $book)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">
                        <a href="{{ route('member.books.show', $book) }}" class="hover:text-ink-secondary">{{ $book->title }}</a>
                    </td>
                    <td class="py-3 px-4">{{ $book->author }}</td>
                    <td class="py-3 px-4">{{ $book->publisher }}</td>
                    <td class="py-3 px-4">{{ $book->page }}</td>
                    <td class="py-3 px-4">{{ $book->isbn }}</td>
                    <td class="py-3 px-4"><x-badge :status="$book->status" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-ink-secondary">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $books->links() }}
</x-layout>
