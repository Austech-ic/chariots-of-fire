<x-member-layout :title="__('Event registrations')">
    @if ($registrations->isEmpty())
        <x-empty-state :title="__('No registrations yet')" icon="users"><a class="btn-dark btn-sm mt-4" href="{{ lroute('events.index') }}">{{ __('See programmes') }}</a></x-empty-state>
    @else
        <div class="card divide-y divide-ink/10">
            @foreach ($registrations as $r)
                <a href="{{ lroute('events.show', $r->event) }}" class="flex flex-wrap items-center justify-between gap-2 p-5 hover:bg-ivory">
                    <span><span class="block font-semibold text-ink">{{ $r->event->tr('title') }}</span><span class="text-sm text-muted">{{ $r->event->localStart()->translatedFormat('l j F Y, g:i a') }} · <span class="font-mono">{{ $r->registration_reference }}</span></span></span>
                    <span class="badge-muted">{{ ucfirst($r->status) }}</span>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $registrations->links() }}</div>
    @endif
</x-member-layout>
