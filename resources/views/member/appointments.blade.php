<x-member-layout :title="__('Appointments')">
    @if ($appointments->isEmpty())
        <x-empty-state :title="__('No appointments yet')" icon="calendar"><a class="btn-dark btn-sm mt-4" href="{{ lroute('counselling.index') }}">{{ __('Counselling') }}</a></x-empty-state>
    @else
        <div class="card divide-y divide-ink/10">
            @foreach ($appointments as $a)
                <a href="{{ route('appointments.manage', $a->reference) }}" class="flex flex-wrap items-center justify-between gap-2 p-5 hover:bg-ivory">
                    <span><span class="block font-semibold text-ink">{{ $a->service->name }}</span><span class="text-sm text-muted">{{ $a->localStart()->translatedFormat('l j F Y, g:i a') }}</span></span>
                    <span class="badge-muted">{{ __(\App\Models\Appointment::STATUSES[$a->status]) }}</span>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $appointments->links() }}</div>
    @endif
</x-member-layout>
