<?php

namespace App\Jobs;

use App\Models\MediaAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Generates responsive WebP variants for images (re-encoding strips EXIF/GPS
 * metadata). Audio, video and documents are stored as-is: shared hosting
 * rarely provides ffmpeg, so long media is best delivered via YouTube.
 */
class ProcessMediaAsset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $assetId) {}

    public function handle(): void
    {
        $asset = MediaAsset::find($this->assetId);
        if (! $asset) {
            return;
        }

        $asset->update(['processing_status' => 'processing', 'processing_error' => null]);

        try {
            if ($asset->kind === 'image' && $asset->mime_type !== 'image/svg+xml') {
                $this->processImage($asset);
            }
            $asset->update(['processing_status' => 'ready']);
        } catch (Throwable $e) {
            Log::warning('Media processing failed', ['asset' => $asset->id, 'error' => $e->getMessage()]);
            $asset->update(['processing_status' => 'failed', 'processing_error' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }

    private function processImage(MediaAsset $asset): void
    {
        ini_set('memory_limit', '512M');

        $manager = new ImageManager(new Driver);
        $disk = Storage::disk($asset->disk);
        $source = $disk->path($asset->path);

        $image = $manager->read($source);
        $width = $image->width();
        $height = $image->height();

        $variants = [];
        $base = preg_replace('/\.[^.]+$/', '', $asset->path);
        foreach (config('fco.media.image_variants') as $name => $targetWidth) {
            if ($width < $targetWidth && $name !== 'sm') {
                continue;
            }
            $variant = $manager->read($source)->scaleDown(width: $targetWidth);
            $path = "{$base}-{$name}.webp";
            $disk->put($path, (string) $variant->toWebp(quality: 80));
            $variants[$name] = ['path' => $path, 'width' => $variant->width(), 'height' => $variant->height(), 'mime' => 'image/webp'];
        }

        $asset->update(['width' => $width, 'height' => $height, 'variants' => $variants]);
    }
}
