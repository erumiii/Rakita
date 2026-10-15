@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => 'books'],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow', 'icon' => 'book-open', 'active' => true],
    ['href' => '/employee/reservations', 'label' => 'Reservations', 'icon' => 'clock'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => 'users'],
];
@endphp

<x-layout title="Borrow" subtitle="Borrowing and return history" :nav="$nav">
    <div class="mb-6 flex justify-end">
        <button type="button" data-modal-target="modal-borrow-create"
                class="inline-flex h-10 items-center rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">+ New Borrowing</button>
    </div>

    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Borrower</th>
                <th class="py-3 px-4 font-medium">Book</th>
                <th class="py-3 px-4 font-medium">Period</th>
                <th class="py-3 px-4 font-medium">Borrowed At</th>
                <th class="py-3 px-4 font-medium">Returned At</th>
                <th class="py-3 px-4 font-medium">Status</th>
                <th class="py-3 px-4 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($borrowings as $borrowing)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">{{ $borrowing->member->name }}</td>
                    <td class="py-3 px-4">{{ $borrowing->book->title }} — {{ $borrowing->book->author }} — {{ $borrowing->book->publisher }} @if($borrowing->book->trashed()) <span class="text-ink-secondary">(deleted)</span> @endif</td
                    >
                    <td class="py-3 px-4">{{ $borrowing->borrow_period_days }} days</td>
                    <td class="py-3 px-4">{{ format_dt($borrowing->borrowed_at) }}</td>
                    <td class="py-3 px-4">{{ format_dt($borrowing->returned_at) }}</td>
                    <td class="py-3 px-4"><x-badge :status="$borrowing->status" /></td>
                    <td class="py-3 px-4">
                        @if (in_array($borrowing->status, ['borrowed', 'overdue']))
                            <button type="button" data-modal-target="modal-return-{{ $borrowing->id }}"
                                    class="inline-flex h-8 items-center rounded-md border border-line-editor bg-white px-3 text-hint text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">Mark Returned</button>
                        @endif
                    </td>
                </tr>
                <tr><td colspan="7" class="p-0">
                    <x-modal :name="'return-'.$borrowing->id" title="Record Return" :action="route('employee.returns.store')">
                        <form method="POST" action="{{ route('employee.returns.store') }}">
                            @csrf
                            <input type="hidden" name="borrowing_id" value="{{ $borrowing->id }}">
                            <p class="text-base text-ink">{{ $borrowing->book->title }} — {{ $borrowing->member->name }}</p>
                            <div class="mt-6 flex flex-col gap-2">
                                <label for="returned_at-{{ $borrowing->id }}" class="text-base text-ink">Returned At</label>
                                <input id="returned_at-{{ $borrowing->id }}" name="returned_at" type="datetime-local" value="{{ old('returned_at', now()->format('Y-m-d\TH:i')) }}"
                                       class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                                <p class="text-hint text-ink-secondary">Leave empty to use the current time.</p>
                                @error('returned_at')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
                            </div>
                            <div class="mt-8 flex justify-end gap-3">
                                <button type="button" data-modal-close
                                        class="inline-flex h-10 items-center rounded-lg border border-line-editor bg-white px-4 text-base text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">Cancel</button>
                                <button type="submit"
                                        class="h-10 rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">Mark Returned</button>
                            </div>
                        </form>
                    </x-modal>
                </td></tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-ink-secondary">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $borrowings->links() }}

    <x-modal name="borrow-create" title="Record Borrowing" :action="route('employee.borrowings.store')"
             :open="old('_modal') === 'borrow-create'">
        <form method="POST" action="{{ route('employee.borrowings.store') }}">
            @csrf
            <input type="hidden" name="_modal" value="borrow-create">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-2">
                    <label for="member_id" class="text-base text-ink">Member</label>
                    <select id="member_id" name="member_id"
                            class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                        <option value="">Select a member</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                    @error('member_id')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="book_id" class="text-base text-ink">Book</label>
                    <select id="book_id" name="book_id"
                            class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                        <option value="">Select a book</option>
                        @foreach ($books as $book)
                            <option value="{{ $book->id }}" @selected(old('book_id') == $book->id)>{{ $book->title }} — {{ $book->author }}</option>
                        @endforeach
                    </select>
                    @error('book_id')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="borrowed_at" class="text-base text-ink">Borrowed At</label>
                    <input id="borrowed_at" name="borrowed_at" type="datetime-local" value="{{ old('borrowed_at', now()->format('Y-m-d\TH:i')) }}"
                           class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                    @error('borrowed_at')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="borrow_period_days" class="text-base text-ink">Period (days)</label>
                    <input id="borrow_period_days" name="borrow_period_days" type="number" min="1" max="7" value="{{ old('borrow_period_days', 7) }}"
                           class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                    <p class="text-hint text-ink-secondary">Maximum 7 days</p>
                    @error('borrow_period_days')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" data-modal-close
                        class="inline-flex h-10 items-center rounded-lg border border-line-editor bg-white px-4 text-base text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">Cancel</button>
                <button type="submit"
                        class="h-10 rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">Save</button>
            </div>
        </form>
    </x-modal>
</x-layout>
