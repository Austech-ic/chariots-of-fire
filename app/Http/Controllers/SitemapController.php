<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Mandate;
use App\Models\Page;
use App\Models\Sermon;
use App\Support\Locales;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $entries = collect(['home', 'about', 'founder', 'live', 'sermons.index', 'events.index', 'gallery.index', 'counselling.index', 'give', 'contact'])
            ->map(fn ($name) => [$name, []]);

        Mandate::query()->published()->get(['slug'])->each(fn ($m) => $entries->push(['mandates.show', [$m->slug]]));
        Page::query()->published()->where('type', 'standard')->get(['slug'])->each(fn ($p) => $entries->push(['pages.show', [$p->slug]]));
        Sermon::query()->published()->where('members_only', false)->get(['slug'])->each(fn ($s) => $entries->push(['sermons.show', [$s->slug]]));
        Event::query()->published()->get(['slug'])->each(fn ($e) => $entries->push(['events.show', [$e->slug]]));
        GalleryAlbum::query()->published()->get(['slug'])->each(fn ($a) => $entries->push(['gallery.show', [$a->slug]]));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
        foreach ($entries as [$name, $params]) {
            $xml .= '<url><loc>'.e(lroute($name, $params, Locales::default())).'</loc>';
            foreach (Locales::enabled() as $locale) {
                $xml .= '<xhtml:link rel="alternate" hreflang="'.e($locale).'" href="'.e(lroute($name, $params, $locale)).'"/>';
            }
            $xml .= '</url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
