<x-layouts.app :title="__('Contact us')">
    <x-page-hero :eyebrow="__('Connect')" :title="__('Contact us')" :intro="__('We would love to hear from you. For confidential pastoral support, please use the counselling page.')" />
    <section class="section">
        <div class="container-site grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <x-flash />
                <form method="POST" action="{{ lroute('contact.store') }}" class="card relative space-y-5 p-6 sm:p-8" data-submit-once>
                    @csrf <x-honeypot />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="name" :label="__('Your name')" required autocomplete="name" />
                        <x-field name="email" type="email" :label="__('Email address')" required autocomplete="email" />
                    </div>
                    <x-field name="phone" type="tel" :label="__('Phone (optional)')" autocomplete="tel" />
                    <x-field name="subject" :label="__('Subject')" required />
                    <x-field name="message" type="textarea" :label="__('Message')" required rows="6" />
                    <p class="text-sm text-muted">{{ __('We use your details only to respond to your enquiry.') }}</p>
                    <button type="submit" class="btn-dark">{{ __('Send message') }}</button>
                </form>
            </div>
            <aside class="space-y-4 lg:col-span-5">
                @foreach ([['map-pin', __('Visit'), setting('contact_address'), null], ['phone', __('Call'), setting('contact_phone'), setting('contact_phone') ? 'tel:'.preg_replace('/[^0-9+]/', '', setting('contact_phone')) : null], ['mail', __('Email'), setting('contact_email'), setting('contact_email') ? 'mailto:'.setting('contact_email') : null], ['whatsapp', 'WhatsApp', setting('whatsapp_number'), setting('whatsapp_number') ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', setting('whatsapp_number')) : null]] as [$icon, $label, $value, $href])
                    @if ($value)
                        <div class="card flex gap-4 p-5">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-gold/15 text-accent"><x-glyph :name="$icon" /></span>
                            <div>
                                <p class="font-semibold text-fg">{{ $label }}</p>
                                @if ($href)<a href="{{ $href }}" class="text-muted hover:text-accent">{{ $value }}</a>@else<p class="text-muted">{{ $value }}</p>@endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </aside>
        </div>
    </section>
</x-layouts.app>
