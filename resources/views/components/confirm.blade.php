@props(['name' => 'confirm'])
<dialog id="modal-{{ $name }}"
        class="m-auto w-[92vw] max-w-sm rounded-lg bg-white p-0 text-ink backdrop:bg-black/30 backdrop:backdrop-blur-[2px]"
        data-modal>
    <div data-modal-frame class="p-6">
        <h2 class="text-section text-ink">Confirm Delete</h2>
        <p data-confirm-message class="mt-1 text-base text-ink-secondary"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-modal-close
                    class="inline-flex h-10 items-center rounded-lg border border-line-editor bg-white px-4 text-base text-ink shadow-nav transition-[background-color,border-color,box-shadow] duration-200 ease-out hover:bg-sidebar">Cancel</button>
            <button type="button" data-confirm-submit
                    class="h-10 rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">Delete</button>
        </div>
    </div>
</dialog>
