<?php

namespace Tests\Feature;

use Tests\TestCase;

class ThemeTest extends TestCase
{
    public function test_light_mode_is_the_default(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-theme="light"', false)
            ->assertDontSee('class="dark"', false)
            ->assertSee('<meta name="color-scheme" content="light">', false)
            ->assertSee('aria-label="Switch to dark mode"', false);
    }

    public function test_dark_mode_is_rendered_from_the_cookie_without_flash(): void
    {
        $this->withUnencryptedCookie('fco_theme', 'dark')->get('/events')->assertOk()
            ->assertSee('class="dark"', false)
            ->assertSee('data-theme="dark"', false)
            ->assertSee('aria-label="Switch to light mode"', false);
    }

    public function test_unknown_cookie_values_fall_back_to_light(): void
    {
        $this->withUnencryptedCookie('fco_theme', '"><script>')->get('/')->assertSee('data-theme="light"', false);
    }

    public function test_no_javascript_toggle_sets_cookie_and_returns(): void
    {
        $this->from('/sermons')->get('/theme/dark')
            ->assertRedirect('/sermons')
            ->assertPlainCookie('fco_theme', 'dark');

        $this->get('/theme/blue')->assertNotFound();
    }

    public function test_error_pages_follow_the_theme(): void
    {
        $this->get('/does-not-exist')->assertNotFound()->assertSee('background:#f7f4ee', false);
        $this->withUnencryptedCookie('fco_theme', 'dark')->get('/does-not-exist')->assertSee('background:#101116', false);
    }
}
