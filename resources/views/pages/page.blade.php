@php $t = $page->translation(); @endphp
<x-layouts.app :title="$t?->meta_title ?? $t?->title" :description="$t?->meta_description ?? $t?->summary" :image="$page->image" :overlay-header="(bool) ($page->image)?->isPublic()">
    <x-page-hero :title="$t?->title" :intro="$t?->summary" :image="$page->image" />
    <section class="section">
        <article class="container-site max-w-3xl prose-content">{!! $t?->body !!}</article>
    </section>
</x-layouts.app>
