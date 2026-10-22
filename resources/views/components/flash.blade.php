@if (session('status'))
    <div role="status" class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-600/20 bg-emerald-50 p-4 text-emerald-900">
        <x-glyph name="check" class="mt-0.5 size-5 shrink-0" /> <p>{{ session('status') }}</p>
    </div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-6 rounded-xl border border-ember/30 bg-ember/5 p-4 text-fg">
        <p class="font-semibold">{{ __('Please check the form:') }}</p>
        <ul class="mt-2 list-disc pl-5 text-sm">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif
