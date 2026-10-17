<?php

namespace App\Http\Controllers;

use App\Models\Sermon;
use App\Models\SermonCategory;
use App\Models\SermonSeries;
use App\Models\Speaker;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SermonController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'topic' => ['nullable', 'string', 'max:100'],
            'speaker' => ['nullable', 'string', 'max:100'],
            'series' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:10'],
            'type' => ['nullable', 'in:audio,video'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = Sermon::query()->published()
            ->with(['translations', 'thumbnail', 'speakers', 'categories.translations', 'media', 'externalSources'])
            ->when(! $request->user(), fn ($q) => $q->where('members_only', false))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                $q->where(fn ($w) => $w->whereHas('translations', fn ($t) => $t->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('scripture_references', 'like', $like))
                    ->orWhereHas('speakers', fn ($s) => $s->where('name', 'like', $like)));
            })
            ->when($filters['topic'] ?? null, fn ($q, $slug) => $q->whereHas('categories', fn ($c) => $c->where('slug', $slug)))
            ->when($filters['speaker'] ?? null, fn ($q, $slug) => $q->whereHas('speakers', fn ($s) => $s->where('slug', $slug)))
            ->when($filters['series'] ?? null, fn ($q, $slug) => $q->whereHas('series', fn ($s) => $s->where('slug', $slug)))
            ->when($filters['language'] ?? null, fn ($q, $lang) => $q->where('language', $lang))
            ->when(($filters['type'] ?? null) === 'audio', fn ($q) => $q->whereHas('media', fn ($m) => $m->where('media_type', 'audio')))
            ->when(($filters['type'] ?? null) === 'video', fn ($q) => $q->where(fn ($w) => $w->whereHas('media', fn ($m) => $m->where('media_type', 'video'))->orWhereHas('externalSources')))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('preached_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('preached_at', '<=', $d))
            ->orderByDesc('preached_at');

        $hasFilters = collect($filters)->filter()->isNotEmpty();

        return view('sermons.index', [
            'sermons' => $query->paginate(12)->withQueryString(),
            'featured' => $hasFilters || $request->integer('page', 1) > 1 ? null
                : Sermon::query()->published()->where('featured', true)->where('members_only', false)->with(['translations', 'thumbnail', 'speakers', 'externalSources'])->orderByDesc('preached_at')->first(),
            'featuredSeries' => $hasFilters ? collect() : SermonSeries::query()->published()->where('is_featured', true)->with(['translations', 'image'])->limit(3)->get(),
            'topics' => SermonCategory::query()->where('status', 'active')->with('translations')->get(),
            'speakers' => Speaker::query()->where('status', 'active')->orderBy('name')->get(),
            'series' => SermonSeries::query()->published()->with('translations')->get(),
            'languages' => Locales::all(),
            'filters' => $filters,
            'hasFilters' => $hasFilters,
        ]);
    }

    public function show(Request $request, Sermon $sermon): View
    {
        abort_unless($sermon->isPubliclyVisible(), 404);
        if ($sermon->members_only && ! $request->user()) {
            return view('sermons.members-only', ['sermon' => $sermon->load('translations')]);
        }

        $sermon->load(['translations', 'thumbnail', 'speakers', 'categories.translations', 'media.asset', 'externalSources', 'series.translations']);

        $related = Sermon::query()->published()->whereKeyNot($sermon->id)->where('members_only', false)
            ->where(function ($q) use ($sermon) {
                $q->when($sermon->series_id, fn ($w) => $w->orWhere('series_id', $sermon->series_id))
                    ->orWhereHas('categories', fn ($c) => $c->whereIn('sermon_categories.id', $sermon->categories->pluck('id')))
                    ->orWhereHas('speakers', fn ($s) => $s->whereIn('speakers.id', $sermon->speakers->pluck('id')));
            })
            ->with(['translations', 'thumbnail', 'externalSources'])->orderByDesc('preached_at')->limit(3)->get();

        return view('sermons.show', ['sermon' => $sermon, 'related' => $related]);
    }
}
