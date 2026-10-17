<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Models\SermonMedia;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves uploaded media from private storage. Public assets are cacheable;
 * private assets are only visible to staff with the media permission.
 */
class MediaController extends Controller
{
    public function show(Request $request, MediaAsset $asset): BinaryFileResponse
    {
        $staff = $request->user()?->hasPermission('media.manage');
        abort_unless($asset->isPublic() || $staff, 404);

        $variant = $request->query('v');
        $variant = is_string($variant) && isset(($asset->variants ?? [])[$variant]) ? $variant : null;
        $path = $asset->absolutePath($variant);
        abort_unless(is_file($path), 404);

        $mime = $variant ? $asset->variants[$variant]['mime'] : $asset->mime_type;

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => $asset->isPublic() ? 'public, max-age=2592000, immutable' : 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }

    /** Downloads are only offered for sermon files explicitly marked downloadable. */
    public function download(Request $request, MediaAsset $asset): BinaryFileResponse
    {
        $allowed = $asset->isPublic() && SermonMedia::query()
            ->where('media_asset_id', $asset->id)
            ->where('download_allowed', true)
            ->whereHas('sermon', fn ($q) => $q->published()->when(! $request->user(), fn ($w) => $w->where('members_only', false)))
            ->exists();
        abort_unless($allowed, 403);

        $ext = pathinfo($asset->path, PATHINFO_EXTENSION);
        $name = str($asset->original_name ?: 'sermon')->beforeLast('.')->slug().'.'.$ext;

        return response()->download($asset->absolutePath(), $name, ['Content-Type' => $asset->mime_type]);
    }
}
