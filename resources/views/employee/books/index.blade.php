@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => 'books', 'active' => true],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow', 'icon' => 'book-open'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => 'users'],
];
@endphp

<x-layout title="Books" subtitle="Manage the library's book collection" :nav="$nav">
    <div class="mb-6 flex items-end justify-between">
        <form method="GET" action="{{ route('employee.books.index') }}" class="flex items-end gap-6">
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
        <button type="button" data-modal-target="modal-book-create"
                class="inline-flex h-10 items-center rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">+ Add Book</button>
    </div>

    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Title</th>
                <th class="py-3 px-4 font-medium">Author</th>
                <th class="py-3 px-4 font-medium">Publisher</th>
                <th class="py-3 px-4 font-medium">Pages</th>
                <th class="py-3 px-4 font-medium">ISBN</th>
                <th class="py-3 px-4 font-medium">Status</th>
                <th class="py-3 px-4 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($books as $book)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">{{ $book->title }}</td>
                    <td class="py-3 px-4">{{ $book->author }}</td>
                    <td class="py-3 px-4">{{ $book->publisher }}</td>
                    <td class="py-3 px-4">{{ $book->page }}</td>
                    <td class="py-3 px-4">{{ $book->isbn }}</td>
                    <td class="py-3 px-4"><x-badge :status="$book->status" /></td>
                    <td class="py-3 px-4">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" data-modal-target="modal-book-edit-{{ $book->id }}" title="Edit" aria-label="Edit"
                                    class="inline-flex size-7 items-center justify-center rounded-md border border-line-editor bg-white text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">
                                <x-icon name="pencil" class="size-3.5" />
                            </button>
                            <form method="POST" action="{{ route('employee.books.destroy', $book) }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" data-confirm="Delete this book?" title="Delete" aria-label="Delete"
                                        class="inline-flex size-7 items-center justify-center rounded-md border border-line-editor bg-white text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">
                                    <x-icon name="trash" class="size-3.5" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <tr class="border-b border-line"><td colspan="7" class="p-0">
                    <x-modal :name="'book-edit-'.$book->id" title="Edit Book" method="PUT" :action="route('employee.books.update', $book)"
                             :open="old('_modal') === 'book-edit-'.$book->id">
                        <form method="POST" action="{{ route('employee.books.update', $book) }}" data-modal-submit>
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_modal" value="book-edit-{{ $book->id }}">
                            <div class="grid grid-cols-2 gap-6">
                                <x-input name="title" label="Title" :value="$book->title" />
                                <x-input name="author" label="Author" :value="$book->author" />
                                <x-input name="publisher" label="Publisher" :value="$book->publisher" />
                                <x-input name="page" label="Page" type="number" :value="$book->page" />
                                <x-input name="isbn" label="ISBN" :value="$book->isbn" />
                            </div>
                            <div class="mt-8 flex justify-end gap-3">
                                <button type="button" data-modal-close
                                        class="inline-flex h-10 items-center rounded-lg border border-line-editor bg-white px-4 text-base text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">Cancel</button>
                                <button type="submit"
                                        class="h-10 rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">Save</button>
                            </div>
                        </form>
                    </x-modal>
                </td></tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-ink-secondary">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <x-modal name="book-create" title="Add Book" method="POST" :action="route('employee.books.store')"
             :open="old('_modal') === 'book-create'">
        <form method="POST" action="{{ route('employee.books.store') }}" data-modal-submit>
            @csrf
            <input type="hidden" name="_modal" value="book-create">
            <div class="grid grid-cols-2 gap-6">
                <x-input name="title" label="Title" placeholder="e.g. The Pragmatic Programmer" />
                <x-input name="author" label="Author" placeholder="e.g. Andrew Hunt" />
                <x-input name="publisher" label="Publisher" placeholder="e.g. Addison-Wesley" />
                <x-input name="page" label="Page" type="number" placeholder="e.g. 352" />
                <x-input name="isbn" label="ISBN" placeholder="e.g. 9780201616224" />
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" data-modal-close
                        class="inline-flex h-10 items-center rounded-lg border border-line-editor bg-white px-4 text-base text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">Cancel</button>
                <button type="submit"
                        class="h-10 rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">Save</button>
            </div>
        </form>
    </x-modal>

    {{ $books->links() }}
</x-layout>
