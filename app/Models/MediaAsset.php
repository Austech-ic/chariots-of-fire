<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    use Concerns\Auditable, HasUlids;

    public const KINDS = ['image' => 'Image', 'audio' => 'Audio', 'video' => 'Video', 'document' => 'Document'];

    protected $fillable = [
        'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'kind', 'visibility', 'processing_status',
        'processing_error', 'checksum', 'variants', 'width', 'height', 'duration_seconds', 'alt_text', 'caption',
        'credit', 'usage_permission', 'uploaded_by',
    ];

    protected $casts = ['variants' => 'array'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function newUniqueId(): string
    {
        return strtolower((string) \Illuminate\Support\Str::ulid());
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isImage(): bool
    {
        return $this->kind === 'image';
    }

    /** Public URL served through the media controller (no symlink required on shared hosting). */
    public function url(?string $variant = null): string
    {
        $params = ['asset' => $this->uuid];
        if ($variant && isset(($this->variants ?? [])[$variant])) {
            $params['v'] = $variant;
        }

        return route('media.show', $params);
    }

    public function downloadUrl(): string
    {
        return route('media.download', $this->uuid);
    }

    /** srcset string built from generated responsive variants. */
    public function srcset(): ?string
    {
        if (! $this->variants) {
            return null;
        }

        return collect($this->variants)
            ->map(fn ($v, $name) => $this->url($name).' '.$v['width'].'w')
            ->implode(', ');
    }

    public function absolutePath(?string $variant = null): string
    {
        $path = $variant ? ($this->variants[$variant]['path'] ?? $this->path) : $this->path;

        return Storage::disk($this->disk)->path($path);
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
