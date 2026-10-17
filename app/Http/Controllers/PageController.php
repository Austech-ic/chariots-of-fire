<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Mandate;
use App\Models\Page;
use App\Models\Sermon;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about', [
            'page' => $this->pageOfType('about'),
            'mandates' => Mandate::query()->published()->with(['translations', 'image'])->orderBy('sort_order')->get(),
            'sermons' => Sermon::query()->published()->where('members_only', false)->with(['translations', 'thumbnail', 'externalSources'])->orderByDesc('preached_at')->limit(3)->get(),
            'events' => Event::query()->published()->upcoming()->with(['translations', 'banner'])->limit(3)->get(),
        ]);
    }

    public function mandate(Mandate $mandate): View
    {
        abort_unless($mandate->isPubliclyVisible(), 404);
        $mandate->load(['translations', 'banner', 'image']);

        return view('pages.mandate', [
            'mandate' => $mandate,
            'others' => Mandate::query()->published()->whereKeyNot($mandate->id)->with('translations')->orderBy('sort_order')->get(),
            'events' => Event::query()->published()->upcoming()->with('translations')->limit(3)->get(),
            'albums' => GalleryAlbum::query()->published()->with(['translations', 'cover'])->latest('taken_at')->limit(3)->get(),
        ]);
    }

    public function founder(): View
    {
        return view('pages.founder', [
            'page' => $this->pageOfType('founder'),
            'sermons' => Sermon::query()->published()->where('members_only', false)->where('featured', true)->with(['translations', 'thumbnail', 'externalSources'])->orderByDesc('preached_at')->limit(3)->get(),
        ]);
    }

    public function show(Page $page): View
    {
        abort_unless($page->isPubliclyVisible(), 404);
        $page->load(['translations', 'image']);

        return view('pages.page', ['page' => $page]);
    }

    private function pageOfType(string $type): ?Page
    {
        return Page::query()->published()->where('type', $type)->with(['translations', 'image'])->first();
    }
}
