<?php

namespace App\Support;

/**
 * Visitor colour theme. Light is the default; the choice is kept in a small,
 * unencrypted cookie so it can be set instantly from JavaScript and read
 * server-side (no flash of the wrong theme on page load).
 */
class Theme
{
    public const COOKIE = 'fco_theme';

    public const MODES = ['light', 'dark'];

    public static function current(): string
    {
        $mode = request()->cookie(self::COOKIE);

        return in_array($mode, self::MODES, true) ? $mode : 'light';
    }

    public static function isDark(): bool
    {
        return self::current() === 'dark';
    }
}
