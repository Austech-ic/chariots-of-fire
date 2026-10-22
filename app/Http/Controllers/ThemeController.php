<?php

namespace App\Http\Controllers;

use App\Support\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

/** No-JavaScript fallback for the light/dark toggle (JS sets the cookie directly). */
class ThemeController extends Controller
{
    public function __invoke(string $mode): RedirectResponse
    {
        abort_unless(in_array($mode, Theme::MODES, true), 404);

        return back(fallback: url('/'))->withCookie(
            Cookie::make(Theme::COOKIE, $mode, 60 * 24 * 365, '/', null, null, false, false, 'lax')
        );
    }
}
