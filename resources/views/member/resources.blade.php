<x-member-layout :title="__('Member resources')">
    @if ($resources->isEmpty())
        <x-empty-state :title="__('No resources available yet')" icon="book-open" />
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($resources as $res)
                <a href="{{ route('member.resources.show', $res) }}" class="card-link p-6">
                    <p class="font-display text-lg font-bold text-fg">{{ $res->title }}</p>
                    @if ($res->description)<p class="mt-2 text-sm text-muted">{{ \Illuminate\Support\Str::limit($res->description, 140) }}</p>@endif
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $resources->links() }}</div>
    @endif
</x-member-layout>
