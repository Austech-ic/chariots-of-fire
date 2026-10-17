<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use App\Services\Streaming\StreamManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('category')->limit(60)->toString() ?: null;

        return view('gallery.index', [
            'albums' => GalleryAlbum::query()->published()
                ->with(['translations', 'cover', 'images' => fn ($q) => $q->limit(1), 'images.mediaAsset'])
                ->when($category, fn ($q) => $q->where('category', $category))
                ->orderByDesc('taken_at')->paginate(12)->withQueryString(),
            'categories' => GalleryAlbum::query()->published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'category' => $category,
        ]);
    }

    public function show(GalleryAlbum $album, StreamManager $streams): View
    {
        abort_unless($album->isPubliclyVisible(), 404);
        $album->load(['translations', 'images.mediaAsset', 'event.translations']);

        $videos = collect($album->video_urls ?? [])
            ->map(fn ($url) => $streams->resolve('youtube', $url) ?? $streams->resolve('vimeo', $url))
            ->filter();

        return view('gallery.show', ['album' => $album, 'videos' => $videos]);
    }
}
