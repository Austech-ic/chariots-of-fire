<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Event;
use App\Models\HeroSlide;
use App\Models\Language;
use App\Models\Mandate;
use App\Models\Page;
use App\Support\Locales;
use Database\Seeders\DemoContentSeeder;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    public function test_every_public_page_renders_with_demo_content(): void
    {
        $this->seed(DemoContentSeeder::class);

        foreach (['/', '/about', '/about/mandates/evangelism', '/founder', '/live', '/sermons', '/sermons/sample-message-1',
            '/events', '/events?view=past', '/events/annual-conference', '/gallery', '/counselling', '/counselling/request',
            '/counselling/book', '/counselling/book?service=spiritual-guidance', '/give', '/contact', '/newsletter/subscribe',
            '/login', '/register', '/forgot-password', '/sitemap.xml', '/api/v1/sermons', '/api/v1/events'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_pages_render_without_any_content(): void
    {
        foreach (['/', '/about', '/founder', '/live', '/sermons', '/events', '/gallery', '/give'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_security_headers_and_csp_are_sent(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('https://www.youtube-nocookie.com', $response->headers->get('Content-Security-Policy'));
    }

    /** FCO-001 */
    public function test_published_slide_appears_within_its_window(): void
    {
        $slide = HeroSlide::create($this->published());
        $slide->translations()->create(['locale' => 'en', 'headline' => 'Welcome to the Feast']);

        $this->get('/')->assertSee('Welcome to the Feast');
    }

    /** FCO-002 */
    public function test_future_slide_stays_hidden_until_start(): void
    {
        $scheduled = HeroSlide::create(['status' => ContentStatus::Scheduled, 'published_at' => now()->addDay()]);
        $scheduled->translations()->create(['locale' => 'en', 'headline' => 'Scheduled headline']);
        $windowed = HeroSlide::create($this->published() + ['starts_at' => now()->addHour()]);
        $windowed->translations()->create(['locale' => 'en', 'headline' => 'Window headline']);

        $this->get('/')->assertDontSee('Scheduled headline')->assertDontSee('Window headline');

        $this->travel(2)->days();
        $this->artisan('fco:publish-scheduled')->assertSuccessful();
        $this->get('/')->assertSee('Scheduled headline')->assertSee('Window headline');
        $this->assertSame(ContentStatus::Published, $scheduled->fresh()->status);
    }

    public function test_draft_content_is_not_public(): void
    {
        $page = Page::where('slug', 'privacy')->first();
        $this->get('/pages/privacy')->assertNotFound();

        $page->update(['status' => ContentStatus::Published]);
        $this->get('/pages/privacy')->assertOk()->assertSee('Privacy notice');

        $mandate = Mandate::create(['slug' => 'draft-mandate', 'status' => ContentStatus::Draft]);
        $mandate->translations()->create(['locale' => 'en', 'title' => 'Hidden']);
        $this->get('/about/mandates/draft-mandate')->assertNotFound();
    }

    /** FCO-022 */
    public function test_language_switching_with_fallback(): void
    {
        Language::where('code', 'fr')->update(['is_enabled' => true]);
        Locales::flush();

        $event = Event::create(['slug' => 'conference', 'starts_at' => now()->addWeek()] + $this->published());
        $event->translations()->create(['locale' => 'en', 'title' => 'Annual Conference']);

        // No French translation yet: English is shown under the /fr prefix.
        $this->get('/fr/events/conference')->assertOk()->assertSee('Annual Conference')->assertHeader('Content-Language', 'fr');

        $event->translations()->create(['locale' => 'fr', 'title' => 'Conférence annuelle']);
        $this->get('/fr/events/conference')->assertSee('Conférence annuelle');
        $this->get('/events/conference')->assertSee('Annual Conference');

        // Hreflang alternates point at the same content in each language.
        $this->get('/events/conference')->assertSee('hreflang="fr" href="http://localhost/fr/events/conference"', false);
    }

    public function test_disabled_or_unknown_locale_prefix_is_not_found(): void
    {
        $this->get('/fr/events')->assertNotFound(); // French seeded but disabled
        $this->get('/zz/events')->assertNotFound();
    }

    public function test_media_for_unpublished_content_stays_private(): void
    {
        $this->get('/media/01jzzzzzzzzzzzzzzzzzzzzzzz')->assertNotFound();
    }
}
