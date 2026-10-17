<x-layouts.app :title="__('Request counselling')" noindex>
    <x-page-hero :eyebrow="__('Counselling')" :title="__('Send a confidential request')" :intro="__('Only authorised counselling staff can read your message.')" />
    <section class="section">
        <div class="container-site max-w-2xl">
            <x-flash />
            <form method="POST" action="{{ lroute('counselling.request.store') }}" class="card relative space-y-5 p-6 sm:p-8" data-submit-once>
                @csrf <x-honeypot />
                @if ($services->isNotEmpty())
                    <div>
                        <label for="service" class="form-label">{{ __('Type of support') }}</label>
                        <select id="service" name="service" class="form-input">
                            <option value="">{{ __('Not sure / general') }}</option>
                            @foreach ($services as $s)<option value="{{ $s->slug }}" @selected(old('service', $selected) === $s->slug)>{{ $s->name }}</option>@endforeach
                        </select>
                    </div>
                @endif
                <x-field name="name" :label="__('Your name')" required autocomplete="name" :value="auth()->user()?->name" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="email" type="email" :label="__('Email')" autocomplete="email" :value="auth()->user()?->email" :help="__('Email or phone is required.')" />
                    <x-field name="phone" type="tel" :label="__('Phone')" autocomplete="tel" :value="auth()->user()?->phone" />
                </div>
                <fieldset>
                    <legend class="form-label">{{ __('How should we contact you?') }}</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach (['email' => __('Email'), 'phone' => __('Phone call'), 'whatsapp' => 'WhatsApp'] as $value => $label)
                            <label class="flex items-center gap-2"><input type="radio" name="preferred_contact" value="{{ $value }}" class="accent-gold-700" @checked(old('preferred_contact', 'email') === $value)> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="form-label">{{ __('Preferred appointment format') }}</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach (\App\Models\CounsellingService::METHODS as $value => $label)
                            <label class="flex items-center gap-2"><input type="radio" name="appointment_method" value="{{ $value }}" class="accent-gold-700" @checked(old('appointment_method') === $value)> {{ __($label) }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <x-field name="message" type="textarea" :label="__('How can we help?')" required rows="6" :help="__('Share only what you are comfortable sharing.')" />
                <x-field name="preferred_times" type="textarea" rows="2" :label="__('When are you usually available?')" :help="__('For example: weekday evenings, Saturday mornings.')" />
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="consent" value="1" required class="mt-1 size-4 accent-gold-700" @checked(old('consent'))>
                    <span>{{ __('I consent to the ministry storing this information securely to arrange counselling, as described in the privacy notice.') }}</span>
                </label>
                <button class="btn-dark"><x-glyph name="lock" class="size-4" /> {{ __('Send securely') }}</button>
            </form>
        </div>
    </section>
</x-layouts.app>
