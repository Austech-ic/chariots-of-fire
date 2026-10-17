<?php

namespace Tests\Unit;

use App\Services\Streaming\EmbedUrlProvider;
use App\Services\Streaming\VimeoProvider;
use App\Services\Streaming\YouTubeProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StreamProvidersTest extends TestCase
{
    public static function youtubeUrls(): array
    {
        return [
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['https://youtu.be/dQw4w9WgXcQ?t=10', 'dQw4w9WgXcQ'],
            ['https://www.youtube.com/live/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
            ['https://youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['https://evil.example/watch?v=dQw4w9WgXcQ', null],
            ['https://www.youtube.com/watch?v=short', null],
            ['javascript:alert(1)', null],
        ];
    }

    #[DataProvider('youtubeUrls')]
    public function test_extracts_youtube_ids(string $input, ?string $expected): void
    {
        $this->assertSame($expected, YouTubeProvider::extractId($input));
    }

    public function test_youtube_embed_uses_privacy_enhanced_domain(): void
    {
        $resolved = (new YouTubeProvider)->resolve('https://youtu.be/dQw4w9WgXcQ');
        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $resolved->embedUrl);
    }

    public function test_youtube_status_is_unknown_without_api_key(): void
    {
        $provider = new YouTubeProvider(null);
        $this->assertFalse($provider->supportsStatusChecks());
        $this->assertSame('unknown', $provider->status('dQw4w9WgXcQ')->status);
    }

    public function test_vimeo_and_generic_embed_providers(): void
    {
        $this->assertSame('76979871', (new VimeoProvider)->resolve('https://vimeo.com/76979871')->externalId);
        $generic = new EmbedUrlProvider(['stream.mux.com']);
        $this->assertNotNull($generic->resolve('https://stream.mux.com/abc.m3u8'));
        $this->assertNull($generic->resolve('https://attacker.example/embed'));
        $this->assertNull($generic->resolve('http://stream.mux.com/abc'));
    }
}
