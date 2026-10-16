@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => 'books'],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow', 'icon' => 'book-open'],
    ['href' => '/employee/reservations', 'label' => 'Reservations', 'icon' => 'clock'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => 'users', 'active' => true],
];
@endphp

<x-layout title="Members" subtitle="Manage library member accounts" :nav="$nav">
    <table class="w-full border-collapse text-center text-base">
        <thead>
            <tr class="border-b border-line text-center text-group text-ink-muted">
                <th class="py-3 px-4 font-medium">Name</th>
                <th class="py-3 px-4 font-medium">Username</th>
                <th class="py-3 px-4 font-medium">Status</th>
                <th class="py-3 px-4 font-medium">Active Borrowings</th>
                <th class="py-3 px-4 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($members as $member)
                <tr class="border-b border-line">
                    <td class="py-3 px-4">{{ $member->name }}</td>
                    <td class="py-3 px-4">{{ $member->username }}</td>
                    <td class="py-3 px-4">
                        @if ($member->is_active)
                            <span class="inline-flex items-center rounded-full border border-line-input px-3 py-0.5 text-hint text-ink">active</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-ink px-3 py-0.5 text-hint text-white">inactive</span>
                        @endif
                    </td>
                    <td class="py-3 px-4">{{ $member->active_borrowings_count }}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center justify-center">
                        <button type="button" data-modal-target="modal-member-edit-{{ $member->id }}" title="Edit" aria-label="Edit"
                                class="inline-flex size-7 items-center justify-center rounded-md border border-line-editor bg-white text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">
                            <x-icon name="pencil" class="size-3.5" />
                        </button>
                    </td>
                </tr>
                <tr class="border-b border-line"><td colspan="5" class="p-0">
                    <x-modal :name="'member-edit-'.$member->id" title="Edit Member" method="PUT" :action="route('employee.members.update', $member)"
                             :open="old('_modal') === 'member-edit-'.$member->id">
                        <form method="POST" action="{{ route('employee.members.update', $member) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_modal" value="member-edit-{{ $member->id }}">
                            <div class="flex flex-col gap-6">
                                <x-input name="name" label="Name" :value="$member->name" />
                                <div class="flex flex-col gap-2">
                                    <label for="password-{{ $member->id }}" class="text-base text-ink">New Password</label>
                                    <input id="password-{{ $member->id }}" name="password" type="password" value=""
                                           placeholder="Enter new password"
                                           class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs placeholder:text-ink-secondary focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                                    <p class="text-hint text-ink-secondary">Leave blank to keep the current password. Minimum 8 characters.</p>
                                    @error('password')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <input id="is_active-{{ $member->id }}" name="is_active" type="checkbox" value="1"
                                           class="size-4 rounded border-line-input"
                                           @checked(old('is_active', $member->is_active))>
                                    <label for="is_active-{{ $member->id }}" class="text-base text-ink">Active</label>
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
                </td></tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-ink-secondary">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $members->links() }}
</x-layout>
