@if ($errors->any())
    <div role="alert" class="mb-6 rounded-lg border border-line-input bg-sidebar px-4 py-3 text-base text-ink">
        <p class="mb-1">Periksa kembali input berikut:</p>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
