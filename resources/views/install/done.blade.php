@extends('install.layout')
@section('content')
    <div class="card p-8">
        <x-glyph name="check" class="size-12 text-accent" />
        <h1 class="mt-4 text-3xl font-extrabold text-fg">Installation complete</h1>
        <ol class="mt-6 list-decimal space-y-3 pl-5 text-body">
            <li>Add <strong>one cron job</strong> in your hosting panel (every minute):<br>
                <code class="mt-1 block overflow-x-auto rounded bg-ink p-3 text-xs text-white">* * * * * php {{ base_path('artisan') }} schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
                This sends emails, processes uploads, publishes scheduled content and reconciles donations.</li>
            <li>Edit <code>.env</code> to add your mail (SMTP) and payment provider keys — see <code>docs/SHARED_HOSTING.md</code>.</li>
            <li>Sign in to the CMS and enable two-factor authentication on your profile.</li>
        </ol>
        <a href="{{ $adminUrl }}" class="btn-dark mt-8">Open the admin dashboard</a>
    </div>
@endsection
