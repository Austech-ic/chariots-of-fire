<?php

namespace Tests\Feature;

use App\Models\Sermon;
use App\Services\Media\MediaLibrary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SermonTest extends TestCase
{
    private function sermon(array $attrs = []): Sermon
    {
        $sermon = Sermon::create(['slug' => 'faith-that-works', 'preached_at' => now()->subWeek()] + $attrs + $this->published());
        $sermon->translations()->create(['locale' => 'en', 'title' => 'Faith that works', 'scripture_references' => 'James 2']);

        return $sermon;
    }

    private function audioAsset(): \App\Models\MediaAsset
    {
        Storage::fake('media');
        // A tiny valid PCM WAV file (1 second of silence) so content sniffing detects audio.
        $samples = str_repeat("\x00\x00", 8000);
        $bytes = 'RIFF'.pack('V', 36 + strlen($samples)).'WAVE'.'fmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', strlen($samples)).$samples;
        $file = UploadedFile::fake()->createWithContent('message.wav', $bytes);

        return app(MediaLibrary::class)->storeUpload($file);
    }

    /** FCO-003 */
    public function test_sermon_with_audio_lists_and_plays(): void
    {
        $sermon = $this->sermon();
        $asset = $this->audioAsset();
        $sermon->media()->create(['media_asset_id' => $asset->id, 'media_type' => 'audio']);

        $this->assertSame('public', $asset->fresh()->visibility, 'Publishing makes attached media public');
        $this->get('/sermons')->assertOk()->assertSee('Faith that works');
        $this->get('/sermons/faith-that-works')->assertOk()->assertSee('<audio', false)->assertSee($asset->fresh()->url(), false);
        $this->get($asset->fresh()->url())->assertOk();
    }

    /** FCO-004 */
    public function test_embeddable_youtube_video_plays(): void
    {
        $sermon = $this->sermon();
        $sermon->externalSources()->create(['provider' => 'youtube', 'external_id' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $this->assertSame('dQw4w9WgXcQ', $sermon->externalSources()->first()->external_id);
        $this->get('/sermons/faith-that-works')->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    }

    /** FCO-005 */
    public function test_non_embeddable_video_shows_unavailable_state_and_link(): void
    {
        $sermon = $this->sermon();
        $sermon->externalSources()->create(['provider' => 'youtube', 'external_id' => 'dQw4w9WgXcQ', 'is_embeddable' => false]);

        $this->get('/sermons/faith-that-works')
            ->assertDontSee('youtube-nocookie.com/embed', false)
            ->assertSee('This video cannot be played here.')
            ->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false);
    }

    /** FCO-006 */
    public function test_permitted_download_succeeds(): void
    {
        $sermon = $this->sermon();
        $asset = $this->audioAsset();
        $sermon->media()->create(['media_asset_id' => $asset->id, 'media_type' => 'audio', 'download_allowed' => true]);

        $this->get("/media/{$asset->uuid}/download")->assertOk()->assertDownload();
    }

    /** FCO-007 */
    public function test_prohibited_or_private_media_is_denied(): void
    {
        $sermon = $this->sermon();
        $asset = $this->audioAsset();
        $sermon->media()->create(['media_asset_id' => $asset->id, 'media_type' => 'audio', 'download_allowed' => false]);
        $this->get("/media/{$asset->uuid}/download")->assertForbidden();

        $private = $this->audioAsset(); // not attached to anything published
        $this->get("/media/{$private->uuid}")->assertNotFound();
        $this->get("/media/{$private->uuid}/download")->assertForbidden();
    }

    public function test_members_only_sermon_requires_sign_in_and_keeps_media_private(): void
    {
        $sermon = $this->sermon(['members_only' => true]);
        $asset = $this->audioAsset();
        $sermon->media()->create(['media_asset_id' => $asset->id, 'media_type' => 'audio']);

        $this->assertSame('private', $asset->fresh()->visibility);
        $this->get('/sermons/faith-that-works')->assertSee('available to registered members');
        $this->get('/sermons')->assertDontSee('Faith that works');
    }

    public function test_search_with_no_results_offers_reset(): void
    {
        $this->sermon();
        $this->get('/sermons?q=nothing-matches-this')->assertSee('No messages match your search')->assertSee('Clear search and filters');
        $this->get('/sermons?q=James')->assertSee('Faith that works');
    }
}
