<x-member-layout :title="__('Welcome, :name', ['name' => \Illuminate\Support\Str::before(auth()->user()->name, ' ')])">
    <div class="grid gap-6 md:grid-cols-2">
        <div class="card p-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-ink"><x-glyph name="calendar" class="size-5 text-gold-700" /> {{ __('Upcoming appointments') }}</h2>
            @forelse ($appointments as $a)
                <a href="{{ route('appointments.manage', $a->reference) }}" class="mt-4 block rounded-lg bg-ivory p-3 hover:bg-ivory-200">
                    <span class="block font-semibold text-ink">{{ $a->service->name }}</span>
                    <span class="text-sm text-muted">{{ $a->localStart()->translatedFormat('D j M, g:i a') }} · {{ __(\App\Models\Appointment::STATUSES[$a->status]) }}</span>
                </a>
            @empty
                <p class="mt-4 text-sm text-muted">{{ __('No upcoming appointments.') }} <a class="font-semibold text-gold-700 underline" href="{{ lroute('counselling.index') }}">{{ __('Book one') }}</a></p>
            @endforelse
        </div>
        <div class="card p-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-ink"><x-glyph name="users" class="size-5 text-gold-700" /> {{ __('Your programmes') }}</h2>
            @forelse ($registrations as $r)
                <a href="{{ lroute('events.show', $r->event) }}" class="mt-4 block rounded-lg bg-ivory p-3 hover:bg-ivory-200">
                    <span class="block font-semibold text-ink">{{ $r->event->tr('title') }}</span>
                    <span class="text-sm text-muted">{{ $r->event->localStart()->translatedFormat('D j M, g:i a') }}</span>
                </a>
            @empty
                <p class="mt-4 text-sm text-muted">{{ __('No upcoming registrations.') }} <a class="font-semibold text-gold-700 underline" href="{{ lroute('events.index') }}">{{ __('See programmes') }}</a></p>
            @endforelse
        </div>
        <div class="card p-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-ink"><x-glyph name="book-open" class="size-5 text-gold-700" /> {{ __('Member resources') }}</h2>
            @forelse ($resources as $res)
                <a href="{{ route('member.resources.show', $res) }}" class="mt-3 block font-semibold text-ink hover:text-gold-700">{{ $res->title }}</a>
            @empty
                <p class="mt-4 text-sm text-muted">{{ __('No resources have been shared with you yet.') }}</p>
            @endforelse
        </div>
        <div class="card p-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-ink"><x-glyph name="heart" class="size-5 text-gold-700" /> {{ __('Giving') }}</h2>
            <p class="mt-4 text-sm text-muted">{{ trans_choice(':count confirmed gift linked to your account|:count confirmed gifts linked to your account', $donationCount, ['count' => $donationCount]) }}</p>
            <div class="mt-4 flex gap-2"><a href="{{ route('member.donations') }}" class="btn-outline btn-sm">{{ __('View history') }}</a><a href="{{ lroute('give') }}" class="btn-primary btn-sm">{{ __('Give') }}</a></div>
        </div>
    </div>
</x-member-layout>
