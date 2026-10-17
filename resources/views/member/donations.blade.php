<x-member-layout :title="__('Giving history')">
    <p class="mb-4 text-sm text-muted">{{ __('Only gifts confirmed by the payment provider and made while signed in to this account are listed.') }}</p>
    @if ($donations->isEmpty())
        <x-empty-state :title="__('No confirmed gifts yet')" icon="heart"><a class="btn-primary btn-sm mt-4" href="{{ lroute('give') }}">{{ __('Give') }}</a></x-empty-state>
    @else
        <div class="card overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-ivory"><tr><th class="p-4">{{ __('Date') }}</th><th class="p-4">{{ __('Purpose') }}</th><th class="p-4">{{ __('Amount') }}</th><th class="p-4">{{ __('Reference') }}</th><th class="p-4">{{ __('Status') }}</th></tr></thead>
                <tbody class="divide-y divide-ink/10">
                    @foreach ($donations as $d)
                        <tr><td class="p-4">{{ local_time($d->paid_at, 'j M Y') }}</td><td class="p-4">{{ $d->campaign?->name ?? __('General giving') }}</td><td class="p-4 font-semibold">{{ $d->formattedAmount() }}</td><td class="p-4 font-mono text-xs">{{ $d->reference }}</td><td class="p-4">{{ __(\App\Models\Donation::STATUSES[$d->status]) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $donations->links() }}</div>
    @endif
</x-member-layout>
