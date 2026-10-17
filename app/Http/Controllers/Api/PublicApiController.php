<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Livestream;
use App\Models\Sermon;
use App\Support\Locales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    private function locale(Request $request): void
    {
        $locale = (string) $request->query('locale', Locales::default());
        app()->setLocale(Locales::isEnabled($locale) ? $locale : Locales::default());
    }

    private function sermonData(Sermon $s): array
    {
        return [
            'slug' => $s->slug,
            'title' => $s->tr('title'),
            'description' => $s->tr('description'),
            'scripture' => $s->tr('scripture_references'),
            'preached_at' => $s->preached_at?->toDateString(),
            'speakers' => $s->speakers->pluck('name'),
            'youtube_id' => $s->youtube()?->external_id,
            'audio_url' => $s->audio()?->asset?->isPublic() ? $s->audio()->asset->url() : null,
            'url' => lroute('sermons.show', $s),
        ];
    }

    private function eventData(Event $e): array
    {
        return [
            'slug' => $e->slug,
            'title' => $e->tr('title'),
            'summary' => $e->tr('summary'),
            'starts_at' => $e->starts_at->toIso8601String(),
            'ends_at' => $e->ends_at?->toIso8601String(),
            'timezone' => $e->timezone,
            'type' => $e->event_type,
            'status' => $e->lifecycle,
            'venue' => $e->tr('venue_name'),
            'url' => lroute('events.show', $e),
        ];
    }

    public function sermons(Request $request): JsonResponse
    {
        $this->locale($request);
        $page = Sermon::query()->published()->where('members_only', false)
            ->with(['translations', 'speakers', 'externalSources', 'media.asset'])->orderByDesc('preached_at')->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(fn ($s) => $this->sermonData($s)),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function sermon(Request $request, Sermon $sermon): JsonResponse
    {
        $this->locale($request);
        abort_unless($sermon->isPubliclyVisible() && ! $sermon->members_only, 404);

        return response()->json(['data' => $this->sermonData($sermon->load(['translations', 'speakers', 'externalSources', 'media.asset']))]);
    }

    public function events(Request $request): JsonResponse
    {
        $this->locale($request);
        $page = Event::query()->published()->upcoming()->with('translations')->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(fn ($e) => $this->eventData($e)),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function event(Request $request, Event $event): JsonResponse
    {
        $this->locale($request);
        abort_unless($event->isPubliclyVisible(), 404);

        return response()->json(['data' => $this->eventData($event->load('translations'))]);
    }

    public function currentStream(): JsonResponse
    {
        $stream = Livestream::current() ?? Livestream::next();

        return response()->json(['data' => $stream ? [
            'title' => $stream->title,
            'status' => $stream->status,
            'scheduled_at' => $stream->scheduled_at?->toIso8601String(),
            'provider' => $stream->provider,
            'watch_url' => $stream->watch_url,
        ] : null]);
    }
}
