<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Notifications\EventRegistrationConfirmed;
use App\Services\Media\MediaLibrary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventTest extends TestCase
{
    private function event(string $slug, $start, array $attrs = []): Event
    {
        $event = Event::create(['slug' => $slug, 'starts_at' => $start] + $attrs + $this->published());
        $event->translations()->create(['locale' => 'en', 'title' => ucfirst(str_replace('-', ' ', $slug)), 'notice' => $attrs['notice'] ?? null]);

        return $event;
    }

    /** FCO-008 */
    public function test_events_list_chronologically_and_split_upcoming_past(): void
    {
        $this->event('later-event', now()->addDays(10));
        $this->event('sooner-event', now()->addDays(2));
        $this->event('old-event', now()->subDays(10));

        $this->get('/events')->assertSeeInOrder(['Sooner event', 'Later event'])->assertDontSee('Old event');
        $this->get('/events?view=past')->assertSee('Old event')->assertDontSee('Sooner event');
    }

    /** FCO-009 */
    public function test_cancelled_event_shows_notice_and_blocks_registration(): void
    {
        $event = $this->event('retreat', now()->addDays(5), ['lifecycle' => 'cancelled', 'registration_enabled' => true]);
        $event->translations()->first()->update(['notice' => 'Weather warning — new date to follow.']);

        $this->get('/events/retreat')->assertSee('This programme has been cancelled.')->assertSee('Weather warning')->assertDontSee('Additional guests');
        $this->post('/events/retreat/register', ['name' => 'A', 'email' => 'a@example.org'])->assertNotFound();
    }

    public function test_registration_respects_capacity_and_is_idempotent_per_email(): void
    {
        Notification::fake();
        $event = $this->event('conference', now()->addDays(5), ['registration_enabled' => true, 'capacity' => 3]);

        $this->post('/events/conference/register', ['name' => 'Ada', 'email' => 'ada@example.org', 'guests' => 1])->assertSessionHas('status');
        $this->post('/events/conference/register', ['name' => 'Ada', 'email' => 'ADA@example.org'])->assertSessionHas('status');
        $this->assertSame(1, $event->registrations()->count());

        $this->post('/events/conference/register', ['name' => 'Bola', 'email' => 'bola@example.org', 'guests' => 1])->assertSessionHasErrors('guests');
        $this->post('/events/conference/register', ['name' => 'Bola', 'email' => 'bola@example.org'])->assertSessionHas('status');
        $this->assertSame(3, $event->confirmedSeats());
        Notification::assertSentOnDemandTimes(EventRegistrationConfirmed::class, 2);
    }

    public function test_calendar_file_is_generated(): void
    {
        $this->event('prayer-night', now()->addDays(3)->setTime(18, 0));
        $response = $this->get('/events/prayer-night/calendar.ics')->assertOk();
        $this->assertStringContainsString('BEGIN:VEVENT', $response->getContent());
        $this->assertStringContainsString('SUMMARY:Prayer night', $response->getContent());
    }

    public function test_weekly_recurrence_generates_occurrences(): void
    {
        $this->event('sunday-service', now()->addDay()->setTime(9, 0), ['recurrence' => ['freq' => 'weekly', 'interval' => 1, 'count' => 4]]);
        $this->artisan('fco:generate-occurrences')->assertSuccessful();
        $this->artisan('fco:generate-occurrences')->assertSuccessful(); // idempotent

        $this->assertSame(3, Event::whereNotNull('parent_event_id')->count());
        $this->assertSame('Sunday service', Event::whereNotNull('parent_event_id')->first()->tr('title'));
    }

    /** FCO-010 */
    public function test_gallery_album_shows_published_images_in_order(): void
    {
        Storage::fake('media');
        $library = app(MediaLibrary::class);
        $album = GalleryAlbum::create(['slug' => 'outreach-day'] + $this->published());
        $album->translations()->create(['locale' => 'en', 'title' => 'Outreach Day']);

        $second = $library->storeUpload(UploadedFile::fake()->image('b.jpg', 800, 600));
        $first = $library->storeUpload(UploadedFile::fake()->image('a.jpg', 800, 600));
        $album->images()->create(['media_asset_id' => $second->id, 'sort_order' => 2, 'caption' => 'Second photo']);
        $album->images()->create(['media_asset_id' => $first->id, 'sort_order' => 1, 'caption' => 'First photo']);

        $this->assertSame('ready', $first->fresh()->processing_status);
        $this->assertArrayHasKey('sm', $first->fresh()->variants);

        $this->get('/gallery')->assertSee('Outreach Day');
        $this->get('/gallery/outreach-day')->assertOk()->assertSeeInOrder(['First photo', 'Second photo']);
        $this->get($first->fresh()->url('sm'))->assertOk()->assertHeader('Content-Type', 'image/webp');
    }
}
