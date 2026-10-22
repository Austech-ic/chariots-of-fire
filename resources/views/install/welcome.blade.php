@extends('install.layout')
@section('content')
    @php $ok = ! in_array(false, $requirements, true); @endphp
    <div class="card p-6 sm:p-8">
        <h1 class="text-2xl font-extrabold text-fg">Server check</h1>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
            @foreach ($requirements as $label => $passed)
                <li class="flex items-center gap-2 text-sm {{ $passed ? 'text-emerald-800' : 'font-semibold text-ember' }}">
                    <x-glyph :name="$passed ? 'check' : 'x'" class="size-4" /> {{ $label }}
                </li>
            @endforeach
        </ul>
        @unless ($ok)
            <p class="mt-4 rounded-lg bg-ember/10 p-3 text-sm text-fg">Fix the items in red (usually in your hosting control panel → “Select PHP version” / “PHP extensions”, or by setting folder permissions to 775), then reload this page.</p>
        @endunless
    </div>

    <form method="POST" action="{{ route('install.run') }}" class="card mt-6 space-y-8 p-6 sm:p-8" data-submit-once>
        @csrf
        <x-flash />
        <section class="space-y-5">
            <h2 class="text-xl font-bold text-fg">1. Website</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="site_name" label="Site name" required value="Flaming Chariot Outreach" />
                <x-field name="app_url" type="url" label="Website address" required :value="request()->getSchemeAndHttpHost()" help="Use https:// once your SSL certificate is active." />
                <x-field name="mail_from" type="email" label="Sender email address" required help="Create this mailbox in your hosting panel first." />
            </div>
        </section>

        <section class="space-y-5">
            <h2 class="text-xl font-bold text-fg">2. Database</h2>
            <p class="text-sm text-muted">Create a MySQL database and user in your hosting panel (cPanel → MySQL Databases), give the user ALL PRIVILEGES, then enter the details here.</p>
            <div>
                <label for="db_connection" class="form-label">Database type</label>
                <select id="db_connection" name="db_connection" class="form-input">
                    <option value="mysql" @selected(old('db_connection', 'mysql') === 'mysql')>MySQL</option>
                    <option value="mariadb" @selected(old('db_connection') === 'mariadb')>MariaDB</option>
                    <option value="pgsql" @selected(old('db_connection') === 'pgsql')>PostgreSQL</option>
                    <option value="sqlite" @selected(old('db_connection') === 'sqlite')>SQLite (small sites / testing only)</option>
                </select>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="db_host" label="Host" value="localhost" />
                <x-field name="db_port" label="Port" type="number" placeholder="3306" />
                <x-field name="db_database" label="Database name" />
                <x-field name="db_username" label="Database user" />
                <x-field name="db_password" type="password" label="Database password" autocomplete="off" />
            </div>
        </section>

        <section class="space-y-5">
            <h2 class="text-xl font-bold text-fg">3. First administrator</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="admin_name" label="Full name" required />
                <x-field name="admin_email" type="email" label="Email" required />
                <x-field name="admin_password" type="password" label="Password" required autocomplete="new-password" help="At least 12 characters, upper and lower case and a number." />
                <x-field name="admin_password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
            </div>
            <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="demo_content" value="1" class="mt-1 size-4 accent-gold-700"> <span>Load sample content (clearly labelled sample slides, sermons and programmes) so you can preview the design. Delete or replace it before launch.</span></label>
        </section>

        <button class="btn-primary w-full text-base" @disabled(! $ok)>Install now</button>
        <p class="text-center text-xs text-muted">The installer locks itself once finished.</p>
    </form>
@endsection
