<?php

namespace App\Services\Media;

use App\Jobs\ProcessMediaAsset;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Validates and stores uploads. MIME types are detected from file contents,
 * never trusted from the client, and filenames are server-generated.
 */
class MediaLibrary
{
    public function allowed(): array
    {
        return config('fco.media.allowed');
    }

    public function kindForMime(string $mime): ?string
    {
        foreach ($this->allowed() as $kind => $rule) {
            if (in_array($mime, $rule['mimes'], true)) {
                return $kind;
            }
        }

        return null;
    }

    /** Validate a file that is already on the media disk (e.g. from a Filament upload). */
    public function inspect(string $path, string $disk = 'media'): array
    {
        $absolute = Storage::disk($disk)->path($path);
        if (! is_file($absolute)) {
            throw new InvalidArgumentException('Uploaded file not found.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute) ?: 'application/octet-stream';
        $kind = $this->kindForMime($mime);
        $size = filesize($absolute);

        if (! $kind) {
            Storage::disk($disk)->delete($path);
            throw new InvalidArgumentException("Files of type {$mime} are not allowed.");
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, $this->allowed()[$kind]['extensions'], true)) {
            Storage::disk($disk)->delete($path);
            throw new InvalidArgumentException("The .{$ext} extension does not match the file contents.");
        }

        $maxBytes = $this->allowed()[$kind]['max_kb'] * 1024;
        if ($size > $maxBytes) {
            Storage::disk($disk)->delete($path);
            throw new InvalidArgumentException('The file is larger than the permitted size.');
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $mime,
            'kind' => $kind,
            'size_bytes' => $size,
            'checksum' => hash_file('sha256', $absolute),
        ];
    }

    /** Create an asset record for a stored file and queue processing. */
    public function register(string $path, array $attributes = [], string $disk = 'media'): MediaAsset
    {
        $asset = MediaAsset::create(array_merge($this->inspect($path, $disk), [
            'processing_status' => 'pending',
            'uploaded_by' => auth()->id(),
        ], $attributes));

        ProcessMediaAsset::dispatch($asset->id);

        return $asset;
    }

    public function storeUpload(UploadedFile $file, array $attributes = []): MediaAsset
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $path = $file->storeAs(self::directory(), Str::lower((string) Str::ulid()).'.'.$ext, 'media');

        return $this->register($path, ['original_name' => mb_substr($file->getClientOriginalName(), 0, 250)] + $attributes);
    }

    public static function directory(): string
    {
        return 'uploads/'.now()->format('Y/m');
    }
}
