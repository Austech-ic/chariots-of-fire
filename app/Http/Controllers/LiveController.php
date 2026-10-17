<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Livestream;
use App\Models\Sermon;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function index(): View
    {
        $live = Livestream::current();

        return view('pages.live', [
            'live' => $live,
            'next' => $live ? null : Livestream::next(),
            'schedule' => Livestream::visible()->where('status', 'scheduled')->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->limit(8)->get(),
            'recordings' => Livestream::visible()->where('status', 'ended')->whereNotNull('recording_sermon_id')->with('recording.translations')->latest('scheduled_at')->limit(6)->get(),
            'sermons' => Sermon::query()->published()->where('members_only', false)->with(['translations', 'thumbnail', 'externalSources'])->orderByDesc('preached_at')->limit(6)->get(),
            'events' => Event::query()->published()->upcoming()->with('translations')->limit(4)->get(),
        ]);
    }
}
