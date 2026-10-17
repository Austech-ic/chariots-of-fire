@php $tz = config('app.display_timezone'); @endphp
<x-layouts.app :title="__('Book an appointment')" noindex>
    <x-page-hero :eyebrow="__('Counselling')" :title="__('Book an appointment')" :intro="__('Times are shown in West Africa Time (Lagos).')" />
    <section class="section">
        <div class="container-site max-w-4xl">
            <x-flash />

            @if ($services->isEmpty())
                <x-empty-state :title="__('Online booking is not available right now')" icon="calendar">
                    <a href="{{ lroute('counselling.request') }}" class="btn-dark btn-sm mt-4">{{ __('Send a request instead') }}</a>
                </x-empty-state>
            @else
                <form method="GET" action="{{ lroute('counselling.book') }}" class="card flex flex-wrap items-end gap-4 p-6">
                    <div class="min-w-60 flex-1">
                        <label for="svc" class="form-label">{{ __('1. Choose a service') }}</label>
                        <select id="svc" name="service" class="form-input" data-auto-submit>
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($services as $s)<option value="{{ $s->slug }}" @selected($service?->is($s))>{{ $s->name }} ({{ $s->duration_minutes }} min)</option>@endforeach
                        </select>
                    </div>
                    <noscript><button class="btn-dark">{{ __('Show times') }}</button></noscript>
                </form>

                @if ($service)
                    @if ($service->requires_account && ! auth()->check())
                        <div class="mt-6 rounded-xl bg-gold/10 p-5">
                            <p class="font-semibold text-ink">{{ __('Please sign in to book this service.') }}</p>
                            <a href="{{ route('login') }}" class="btn-dark btn-sm mt-3">{{ __('Sign in') }}</a>
                        </div>
                    @else
                        <form method="POST" action="{{ lroute('counselling.book.store') }}" class="relative mt-6 space-y-6" data-submit-once>
                            @csrf <x-honeypot />
                            <input type="hidden" name="service" value="{{ $service->slug }}">
                            <div class="card p-6">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h2 class="text-lg font-bold text-ink">{{ __('2. Pick a time') }}</h2>
                                    <div class="flex gap-2 text-sm">
                                        @if ($weekStart->gt(now($tz)->startOfDay()))
                                            <a class="btn-outline btn-sm" href="{{ lroute('counselling.book', ['service' => $service->slug, 'week' => $weekStart->subDays(7)->toDateString()]) }}"><x-glyph name="arrow-left" class="size-4" /> {{ __('Earlier') }}</a>
                                        @endif
                                        <a class="btn-outline btn-sm" href="{{ lroute('counselling.book', ['service' => $service->slug, 'week' => $weekStart->addDays(7)->toDateString()]) }}">{{ __('Later') }} <x-glyph name="arrow-right" class="size-4" /></a>
                                    </div>
                                </div>
                                @error('slot')<p class="form-error" role="alert">{{ $message }}</p>@enderror
                                @if ($slots->isEmpty())
                                    <p class="mt-6 text-muted">{{ __('No times are available this week. Try later dates, or') }} <a href="{{ lroute('counselling.request', ['service' => $service->slug]) }}" class="font-semibold text-gold-700 underline">{{ __('send a request') }}</a>.</p>
                                @else
                                    <fieldset class="mt-6 space-y-6">
                                        <legend class="sr-only">{{ __('Available times') }}</legend>
                                        @foreach ($slots as $day => $daySlots)
                                            <div>
                                                <p class="text-sm font-bold text-ink">{{ \Carbon\Carbon::parse($day, $tz)->translatedFormat('l j F') }}</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach ($daySlots as $slot)
                                                        <label class="cursor-pointer">
                                                            <input type="radio" name="slot" value="{{ $slot->key() }}" class="peer sr-only" required @checked(old('slot') === $slot->key())>
                                                            <span class="block rounded-full border border-ink/15 bg-white px-4 py-2 text-sm font-medium text-ink peer-checked:border-ink peer-checked:bg-ink peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-gold">
                                                                {{ $slot->start->setTimezone($tz)->format('g:i a') }}
                                                                @if ($counsellors->count() > 1)<span class="opacity-70">· {{ $counsellors[$slot->counsellorId] ?? '' }}</span>@endif
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </fieldset>
                                @endif
                            </div>

                            @if ($slots->isNotEmpty())
                                <div class="card space-y-5 p-6">
                                    <h2 class="text-lg font-bold text-ink">{{ __('3. Your details') }}</h2>
                                    <fieldset>
                                        <legend class="form-label">{{ __('Appointment format') }}</legend>
                                        <div class="flex flex-wrap gap-4">
                                            @foreach ($service->methods() as $i => $method)
                                                <label class="flex items-center gap-2"><input type="radio" name="method" value="{{ $method }}" required class="accent-gold-700" @checked(old('method', $service->methods()[0]) === $method)> {{ __(\App\Models\CounsellingService::METHODS[$method]) }}</label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <x-field name="name" :label="__('Your name')" required autocomplete="name" :value="auth()->user()?->name" />
                                        <x-field name="email" type="email" :label="__('Email')" required autocomplete="email" :value="auth()->user()?->email" />
                                    </div>
                                    <x-field name="phone" type="tel" :label="__('Phone (optional)')" autocomplete="tel" :value="auth()->user()?->phone" />
                                    <label class="flex items-start gap-3 text-sm">
                                        <input type="checkbox" name="consent" value="1" required class="mt-1 size-4 accent-gold-700">
                                        <span>{{ __('I consent to the ministry storing these details to arrange my appointment.') }}</span>
                                    </label>
                                    <p class="text-sm text-muted">
                                        {{ $service->approval_required ? __('Your booking will be reviewed and you will receive a confirmation email.') : __('Your booking will be confirmed immediately.') }}
                                    </p>
                                    <button class="btn-dark">{{ __('Book appointment') }}</button>
                                </div>
                            @endif
                        </form>
                    @endif
                @endif
            @endif
        </div>
    </section>
</x-layouts.app>
