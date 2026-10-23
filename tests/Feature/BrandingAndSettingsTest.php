<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Services\Media\MediaLibrary;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingAndSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    private function image(string $name = 'logo.png'): MediaAsset
    {
        return app(MediaLibrary::class)->storeUpload(UploadedFile::fake()->image($name, 400, 120));
    }

    public function test_site_name_and_flame_mark_are_shown_without_a_logo(): void
    {
        Settings::set('site_name', 'Example Ministry');
        Settings::flush();

        $this->get('/')->assertOk()
            ->assertSee('Example')
            ->assertSee('Ministry')
            ->assertSee('favicon.svg', false);
    }

    public function test_administrator_can_upload_a_logo_from_site_settings(): void
    {
        $logo = $this->image();
        $favicon = $this->image('icon.png');
        $this->assertFalse($logo->isPublic());

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->staff('administrator'));
        Livewire::test(SiteSettings::class)
            ->fillForm(['logo_asset_id' => $logo->id, 'favicon_asset_id' => $favicon->id, 'show_site_name' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($logo->fresh()->isPublic(), 'Logo becomes publicly visible');
        $this->assertSame('0', Settings::get('show_site_name'));

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString($logo->fresh()->url(), $html, 'Logo appears in the header');
        $this->assertStringContainsString('rel="icon" href="'.e($favicon->fresh()->url()), $html, 'Custom favicon');
        $this->assertStringContainsString('og:image', $html, 'Logo doubles as share image');
    }

    public function test_private_assets_are_never_used_as_logo(): void
    {
        $logo = $this->image();
        Settings::set('logo_asset_id', $logo->id);
        Settings::flush();

        $this->assertStringNotContainsString($logo->uuid, $this->get('/')->getContent());
    }

    public function test_menu_pages_contact_details_and_bank_details_are_shown(): void
    {
        $page = Page::create(['slug' => 'our-beliefs'] + $this->published());
        $page->translations()->create(['locale' => 'en', 'title' => 'Our Beliefs', 'body' => '<p>Text</p>']);

        Settings::set('menu_page_ids', (string) $page->id);
        Settings::set('service_times', "Sunday 8:00 am — Worship service");
        Settings::set('bank_transfer_details', "Account name: Placeholder\nAccount number: 0000000000");
        Settings::set('announcement_text', 'Special programme this Friday');
        Settings::set('map_embed_url', 'https://evil.example.com/frame');
        Settings::flush();

        $this->get('/')->assertSee('Our Beliefs')->assertSee('Special programme this Friday')->assertSee('Sunday 8:00 am');
        $this->get('/contact')->assertSee('Sunday 8:00 am')->assertDontSee('evil.example.com', false);
        $this->get('/give')->assertSee('Account number: 0000000000');
    }

    public function test_system_status_is_limited_to_settings_managers(): void
    {
        $this->actingAs($this->staff('super-admin'))->get('/admin/system-status')->assertOk()
            ->assertSee('Health checks')->assertSee('PHP');
        $this->actingAs($this->staff('editor'))->get('/admin/system-status')->assertForbidden();
    }

    public function test_scheduler_heartbeat_is_recorded(): void
    {
        $this->artisan('schedule:run')->assertSuccessful();

        $this->assertNotNull(Cache::get('fco.scheduler.last_run'));
    }
}
