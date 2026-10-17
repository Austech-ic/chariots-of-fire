<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\Livestream;
use App\Models\Mandate;
use App\Models\Sermon;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $sections = HomeSection::query()->with('translations')->where('is_visible', true)->orderBy('sort_order')->get();

        return view('pages.home', [
            'slides' => HeroSlide::query()->active()->with(['translations', 'image'])->get(),
            'sections' => $sections,
            'mandates' => Mandate::query()->published()->with(['translations', 'image'])->orderBy('sort_order')->limit(6)->get(),
            'liveStream' => Livestream::current(),
            'nextStream' => Livestream::next(),
            'sermons' => Sermon::query()->published()->where('members_only', false)->with(['translations', 'thumbnail', 'speakers', 'externalSources'])
                ->orderByDesc('preached_at')->limit(3)->get(),
            'events' => Event::query()->published()->upcoming()->with(['translations', 'banner'])->limit(3)->get(),
            'albums' => GalleryAlbum::query()->published()->with(['translations', 'cover', 'images.mediaAsset'])->latest('taken_at')->limit(4)->get(),
        ]);
    }
}
