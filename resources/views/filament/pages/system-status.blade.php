<x-filament-panels::page>
    {{-- Filament only ships the utility classes it uses itself, so this page carries its own small stylesheet. --}}
    <style>
        .fco-status-alert { border: 1px solid var(--danger-300); background: var(--danger-50); color: var(--danger-800); border-radius: 0.75rem; padding: 1rem; font-size: 0.875rem; }
        .dark .fco-status-alert { border-color: var(--danger-700); background: var(--danger-950); color: var(--danger-200); }
        .fco-checks { list-style: none; margin: 0; padding: 0; }
        .fco-checks li { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0; border-top: 1px solid var(--gray-100); }
        .dark .fco-checks li { border-color: rgb(255 255 255 / 0.06); }
        .fco-checks li:first-child { border-top: 0; padding-top: 0; }
        .fco-checks .fco-icon { width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.1rem; }
        .fco-ok { color: var(--success-600); }
        .fco-bad { color: var(--danger-600); }
        .fco-na { color: var(--gray-400); }
        .fco-label { font-size: 0.875rem; font-weight: 600; color: var(--gray-950); margin: 0; }
        .dark .fco-label, .dark .fco-env dd { color: #fff; }
        .fco-detail, .fco-env dt, .fco-note { font-size: 0.875rem; color: var(--gray-500); margin: 0; }
        .dark .fco-detail, .dark .fco-env dt, .dark .fco-note { color: var(--gray-400); }
        .fco-env { display: grid; gap: 0.75rem 1.5rem; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); margin: 0; }
        .fco-env dd { margin: 0; font-size: 0.875rem; font-weight: 500; color: var(--gray-950); }
        .fco-note { margin-top: 1rem; font-size: 0.75rem; }
    </style>

    @if ($pending)
        <div class="fco-status-alert">
            <strong>{{ count($pending) }} database update(s) are waiting.</strong>
            A super administrator should click <em>Run database updates</em> above.
        </div>
    @endif

    <x-filament::section heading="Health checks">
        <ul class="fco-checks">
            @foreach ($checks as [$label, $ok, $detail])
                <li>
                    @if ($ok === true)
                        <x-filament::icon icon="heroicon-s-check-circle" class="fco-icon fco-ok" />
                    @elseif ($ok === false)
                        <x-filament::icon icon="heroicon-s-exclamation-triangle" class="fco-icon fco-bad" />
                    @else
                        <x-filament::icon icon="heroicon-o-minus-circle" class="fco-icon fco-na" />
                    @endif
                    <div>
                        <p class="fco-label">{{ $label }}</p>
                        <p class="fco-detail">{{ $detail }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-filament::section>

    <x-filament::section heading="Environment" collapsible>
        <dl class="fco-env">
            @foreach ($environment as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
        <p class="fco-note">Passwords and API keys live in the server’s <code>.env</code> file and are never shown here.</p>
    </x-filament::section>
</x-filament-panels::page>
