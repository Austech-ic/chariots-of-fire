<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Notifications\EventRegistrationConfirmed;
use App\Support\Reference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $view = $request->query('view') === 'past' ? 'past' : 'upcoming';
        $category = $request->string('category')->limit(60)->toString() ?: null;

        $query = Event::query()->published()->with(['translations', 'banner'])
            ->when($category, fn ($q) => $q->where('category', $category));

        return view('events.index', [
            'events' => ($view === 'past' ? $query->past() : $query->upcoming())->paginate(12)->withQueryString(),
            'featured' => $view === 'upcoming' && ! $category
                ? Event::query()->published()->upcoming()->where('featured', true)->with(['translations', 'banner'])->first()
                : null,
            'categories' => Event::query()->published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'view' => $view,
            'category' => $category,
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless($event->isPubliclyVisible(), 404);
        $event->load(['translations', 'banner', 'livestreams' => fn ($q) => $q->visible()]);

        return view('events.show', [
            'event' => $event,
            'seatsLeft' => $event->capacity ? max(0, $event->capacity - $event->confirmedSeats()) : null,
        ]);
    }

    public function ics(Event $event): Response
    {
        abort_unless($event->isPubliclyVisible(), 404);
        $esc = fn (?string $v) => addcslashes(str_replace(["\r\n", "\n"], '\n', (string) $v), ',;\\');
        $end = $event->ends_at ?? $event->starts_at->copy()->addHours(2);

        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Flaming Chariot Outreach//Events//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$event->starts_at->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$end->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$esc($event->tr('title')),
            'DESCRIPTION:'.$esc(strip_tags((string) $event->tr('summary'))."\n".lroute('events.show', $event)),
            'LOCATION:'.$esc(trim($event->tr('venue_name').' '.$event->tr('venue_address')) ?: $event->online_url),
            'URL:'.lroute('events.show', $event),
            'STATUS:'.($event->isCancelled() ? 'CANCELLED' : 'CONFIRMED'),
            'END:VEVENT', 'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->slug.'.ics"',
        ]);
    }

    public function register(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->isPubliclyVisible() && $event->acceptsRegistrations(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'guests' => ['nullable', 'integer', 'min:0', 'max:10'],
            'website' => ['prohibited'], // honeypot
        ]);

        $registration = DB::transaction(function () use ($event, $data, $request) {
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->first();
            $existing = $locked->registrations()->where('email', strtolower($data['email']))->where('status', 'confirmed')->first();
            if ($existing) {
                return $existing;
            }

            $seats = 1 + (int) ($data['guests'] ?? 0);
            if ($locked->capacity && $locked->confirmedSeats() + $seats > $locked->capacity) {
                return null;
            }

            return EventRegistration::create([
                'event_id' => $locked->id,
                'user_id' => $request->user()?->id,
                'registration_reference' => Reference::make('REG'),
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'guests' => (int) ($data['guests'] ?? 0),
                'status' => 'confirmed',
            ]);
        });

        if (! $registration) {
            return back()->withInput()->withErrors(['guests' => __('Sorry, this programme is fully booked.')]);
        }

        if ($registration->wasRecentlyCreated) {
            Notification::route('mail', $registration->email)->notify(new EventRegistrationConfirmed($registration));
        }

        return back()->with('status', __('You are registered. Your reference is :ref.', ['ref' => $registration->registration_reference]));
    }
}
