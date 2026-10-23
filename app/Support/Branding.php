<?php

namespace App\Support;

use App\Models\MediaAsset;

/**
 * Logo, favicon and default share image chosen in Site settings.
 * Everything falls back to the built-in flame mark when nothing is uploaded.
 */
class Branding
{
    /** @var array<string, MediaAsset|null> */
    private static array $cache = [];

    private static function asset(string $key): ?MediaAsset
    {
        if (! array_key_exists($key, self::$cache)) {
            $id = Settings::get($key);
            self::$cache[$key] = $id ? rescue(fn () => MediaAsset::query()->whereKey($id)->where('kind', 'image')->first(), null, false) : null;
        }
        $asset = self::$cache[$key];

        return $asset && $asset->isPublic() ? $asset : null;
    }

    /** Logo for light backgrounds (light-mode header). */
    public static function logo(): ?MediaAsset
    {
        return self::asset('logo_asset_id') ?? self::asset('logo_dark_asset_id');
    }

    /** Logo for dark backgrounds (dark mode, photo headers, footer). */
    public static function logoOnDark(): ?MediaAsset
    {
        return self::asset('logo_dark_asset_id') ?? self::asset('logo_asset_id');
    }

    public static function favicon(): ?MediaAsset
    {
        return self::asset('favicon_asset_id');
    }

    public static function faviconUrl(): string
    {
        return self::favicon()?->url() ?? asset('favicon.svg');
    }

    public static function shareImage(): ?MediaAsset
    {
        return self::asset('og_image_asset_id') ?? self::logo();
    }

    public static function showName(): bool
    {
        return self::logo() === null || Settings::get('show_site_name', '1') === '1';
    }

    public static function logoHeight(): int
    {
        return max(24, min(80, (int) Settings::get('logo_height', 40)));
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}
