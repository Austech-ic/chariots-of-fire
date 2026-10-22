<x-member-layout :title="__('Communication preferences')">
    <form method="POST" action="{{ route('member.preferences.update') }}" class="card space-y-8 p-6">
        @csrf @method('PUT')
        <div>
            <h2 class="text-lg font-bold text-fg">{{ __('Email newsletter') }}</h2>
            <label class="mt-3 flex items-start gap-3">
                <input type="checkbox" name="newsletter" value="1" class="mt-1 size-4 accent-gold-700" @checked($newsletter?->status === 'active')>
                <span>{{ __('Send me ministry news and programme announcements by email.') }}</span>
            </label>
        </div>
        <div>
            <h2 class="text-lg font-bold text-fg">{{ __('SMS and WhatsApp') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('Optional. Choose exactly what you want to receive; you can opt out at any time and it takes effect immediately.') }}</p>
            <x-field class="mt-4 max-w-sm" name="phone" type="tel" :label="__('Mobile number (with country code)')" :value="$user->phone" placeholder="+234…" />
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b border-line"><th class="py-2 pr-4 font-semibold">{{ __('Message type') }}</th>@foreach (\App\Models\NotificationConsent::CHANNELS as $label)<th class="px-4 py-2 font-semibold">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach (\App\Models\NotificationConsent::PURPOSES as $purpose => $plabel)
                            <tr class="border-b border-line">
                                <td class="py-3 pr-4">{{ __($plabel) }}</td>
                                @foreach (\App\Models\NotificationConsent::CHANNELS as $channel => $clabel)
                                    <td class="px-4 py-3"><input type="checkbox" name="channels[]" value="{{ $channel }}.{{ $purpose }}" aria-label="{{ __($plabel) }} — {{ $clabel }}" class="size-4 accent-gold-700" @checked(in_array($channel.'.'.$purpose, $consents, true))></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <button class="btn-dark">{{ __('Save preferences') }}</button>
    </form>
</x-member-layout>
