<x-auth-card :title="__('Reset your password')" :intro="__('Enter your email and we will send you a reset link.')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" data-submit-once>
        @csrf
        <x-field name="email" type="email" :label="__('Email')" required autocomplete="email" />
        <button class="btn-dark w-full">{{ __('Email reset link') }}</button>
    </form>
</x-auth-card>
