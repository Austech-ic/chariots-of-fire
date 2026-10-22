<x-member-layout :title="__('Account requests')">
    <form method="POST" action="{{ route('member.account.request') }}" class="card space-y-5 p-6">
        @csrf
        <p class="text-muted">{{ __('Ask us to deactivate or delete your account, send you a copy of your data, or correct your details. Some financial records may need to be kept for legal reasons; we will explain if so.') }}</p>
        <div>
            <label for="type" class="form-label">{{ __('Request type') }}</label>
            <select id="type" name="type" class="form-input" required>
                @foreach (\App\Models\AccountRequest::TYPES as $value => $label)<option value="{{ $value }}">{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <x-field name="details" type="textarea" rows="3" :label="__('Details (optional)')" />
        <button class="btn-dark">{{ __('Submit request') }}</button>
    </form>
    @if ($requests->isNotEmpty())
        <h2 class="mt-10 text-lg font-bold text-fg">{{ __('Previous requests') }}</h2>
        <ul class="card mt-4 divide-y divide-line">
            @foreach ($requests as $r)
                <li class="flex justify-between p-4 text-sm"><span>{{ __(\App\Models\AccountRequest::TYPES[$r->type]) }} · {{ local_time($r->created_at, 'j M Y') }}</span><span class="badge-muted">{{ ucfirst($r->status) }}</span></li>
            @endforeach
        </ul>
    @endif
</x-member-layout>
