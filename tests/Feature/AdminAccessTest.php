<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Filament\Resources\Mandates\Pages\CreateMandate;
use App\Models\CounsellingRequest;
use App\Models\CounsellorProfile;
use App\Models\Mandate;
use App\Services\Media\MediaLibrary;
use Database\Seeders\DemoContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    private const ALL_INDEXES = [
        'pages', 'hero-slides', 'home-sections', 'mandates', 'media-assets', 'sermons', 'sermon-series', 'speakers',
        'sermon-categories', 'livestreams', 'events', 'event-registrations', 'gallery-albums', 'counselling-requests',
        'appointments', 'counselling-services', 'counsellor-profiles', 'donations', 'donation-campaigns',
        'newsletter-subscriptions', 'contact-enquiries', 'members', 'member-resources', 'account-requests', 'staff',
        'roles', 'languages', 'audit-logs', 'webhook-events',
    ];

    public function test_members_and_guests_cannot_reach_the_cms(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs($this->member())->get('/admin')->assertForbidden();
    }

    public function test_super_admin_can_open_every_screen(): void
    {
        $this->seed(DemoContentSeeder::class);
        $admin = $this->staff('super-admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/site-settings')->assertOk();
        foreach (self::ALL_INDEXES as $slug) {
            $this->assertSame(200, $this->actingAs($admin)->get("/admin/{$slug}")->status(), "/admin/{$slug}");
        }
        foreach (['pages', 'hero-slides', 'mandates', 'media-assets', 'sermons', 'events', 'gallery-albums', 'livestreams', 'counsellor-profiles', 'staff'] as $slug) {
            $this->assertSame(200, $this->actingAs($admin)->get("/admin/{$slug}/create")->status(), "/admin/{$slug}/create");
        }
        foreach (['sermons' => \App\Models\Sermon::first(), 'events' => \App\Models\Event::first(), 'mandates' => Mandate::first(), 'counsellor-profiles' => CounsellorProfile::first()] as $slug => $record) {
            $this->assertSame(200, $this->actingAs($admin)->get("/admin/{$slug}/{$record->getRouteKey()}/edit")->status(), "/admin/{$slug} edit");
        }
    }

    /** FCO-024 */
    public function test_editor_cannot_reach_confidential_or_financial_areas(): void
    {
        $editor = $this->staff('editor');

        $this->actingAs($editor)->get('/admin/sermons')->assertOk();
        foreach (['counselling-requests', 'appointments', 'donations', 'staff', 'roles', 'audit-logs', 'site-settings'] as $slug) {
            $this->actingAs($editor)->get("/admin/{$slug}")->assertForbidden();
        }
    }

    public function test_general_administrator_cannot_read_counselling_records(): void
    {
        $request = CounsellingRequest::create(['reference' => 'CR-1', 'contact_data' => ['name' => 'X'], 'encrypted_message' => 'secret', 'status' => 'new']);
        $admin = $this->staff('administrator');

        $this->actingAs($admin)->get('/admin/counselling-requests')->assertForbidden();
        $this->assertContains($this->actingAs($admin)->get("/admin/counselling-requests/{$request->id}")->status(), [403, 404]);
        $this->assertFalse($admin->can('view', $request));
    }

    public function test_counsellor_sees_only_assigned_requests_and_access_is_audited(): void
    {
        $counsellorUser = $this->staff('counsellor');
        $profile = CounsellorProfile::create(['user_id' => $counsellorUser->id, 'display_name' => 'Mine']);
        $mine = CounsellingRequest::create(['reference' => 'CR-MINE', 'contact_data' => ['name' => 'A'], 'encrypted_message' => 'My case', 'status' => 'assigned', 'assigned_to' => $profile->id]);
        $other = CounsellingRequest::create(['reference' => 'CR-OTHER', 'contact_data' => ['name' => 'B'], 'encrypted_message' => 'Not yours', 'status' => 'new']);

        $this->actingAs($counsellorUser)->get('/admin/counselling-requests')->assertOk()->assertSee('CR-MINE')->assertDontSee('CR-OTHER');
        $this->actingAs($counsellorUser)->get("/admin/counselling-requests/{$mine->id}")->assertOk()->assertSee('My case');
        $this->actingAs($counsellorUser)->get("/admin/counselling-requests/{$other->id}")->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['action' => 'CounsellingRequest.viewed', 'subject_id' => $mine->id, 'actor_user_id' => $counsellorUser->id]);
    }

    public function test_editor_without_publish_permission_can_only_submit_for_review(): void
    {
        $editor = $this->staff('editor');
        $this->actingAs($editor);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $mandate = Mandate::create(['slug' => 'test', 'status' => ContentStatus::Published]);
        $this->assertSame(ContentStatus::InReview, $mandate->fresh()->status);
        $this->get('/about/mandates/test')->assertNotFound();

        $this->actingAs($this->staff('publisher'));
        $mandate->update(['status' => ContentStatus::Published]);
        $this->assertSame(ContentStatus::Published, $mandate->fresh()->status);
    }

    public function test_cms_form_requires_default_language_translation(): void
    {
        $this->actingAs($this->staff('publisher'));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateMandate::class)
            ->fillForm(['slug' => 'missing-en', 'status' => 'published', 'translations' => [['locale' => 'fr', 'title' => 'Seulement français', 'is_approved' => true]]])
            ->call('create')
            ->assertHasFormErrors();
        $this->assertDatabaseMissing('mandates', ['slug' => 'missing-en']);
    }

    public function test_malicious_upload_is_rejected(): void
    {
        Storage::fake('media');
        $path = 'uploads/'.Str::random(8).'.jpg';
        Storage::disk('media')->put($path, "<?php system(\$_GET['c']); ?>");

        $this->expectException(InvalidArgumentException::class);
        try {
            app(MediaLibrary::class)->register($path);
        } finally {
            Storage::disk('media')->assertMissing($path);
        }
    }

    public function test_image_with_wrong_extension_is_rejected(): void
    {
        Storage::fake('media');
        $file = UploadedFile::fake()->image('photo.png');
        $path = Storage::disk('media')->putFileAs('uploads', $file, 'photo.pdf');

        $this->expectException(InvalidArgumentException::class);
        app(MediaLibrary::class)->register($path);
    }
}
