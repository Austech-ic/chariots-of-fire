<x-auth-card :title="__('Create a member account')" :intro="__('Access member resources, your appointments and your giving history.')">
    <form method="POST" action="{{ route('register') }}" class="relative space-y-5" data-submit-once>
        @csrf <x-honeypot />
        <x-field name="name" :label="__('Full name')" required autocomplete="name" />
        <x-field name="email" type="email" :label="__('Email')" required autocomplete="email" />
        <x-field name="password" type="password" :label="__('Password')" required autocomplete="new-password" :help="__('At least 10 characters with letters and numbers.')" />
        <x-field name="password_confirmation" type="password" :label="__('Confirm password')" required autocomplete="new-password" />
        <label class="flex items-start gap-3 text-sm">
            <input type="checkbox" name="privacy" value="1" required class="mt-1 size-4 accent-gold-700">
            <span>{{ __('I have read the privacy notice and agree to the ministry storing my account details.') }}</span>
        </label>
        <button class="btn-dark w-full">{{ __('Create account') }}</button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">{{ __('Already registered?') }} <a href="{{ route('login') }}" class="font-semibold text-gold-700 underline">{{ __('Sign in') }}</a></p>
</x-auth-card>
