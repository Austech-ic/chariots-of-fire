<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\MinistryOverview;
use App\Http\Middleware\EnsureAccountActive;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Enums\ThemeMode;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            // Two-factor authentication (authenticator app) for staff; set REQUIRE_STAFF_MFA=true to enforce it.
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: (bool) config('fco.require_staff_mfa'))
            ->brandName(fn () => setting('site_name').' · CMS')
            // Logos and favicon follow Site settings (falls back to the name when no logo is uploaded).
            ->brandLogo(fn () => \App\Support\Branding::logo()?->url())
            ->darkModeBrandLogo(fn () => \App\Support\Branding::logoOnDark()?->url())
            ->brandLogoHeight('2.25rem')
            // Light by default; staff can switch to dark or system from the user menu.
            ->defaultThemeMode(ThemeMode::Light)
            ->favicon(fn () => \App\Support\Branding::faviconUrl())
            ->colors([
                'primary' => Color::hex('#a8823a'),
                'gray' => Color::Zinc,
            ])
            ->font('Inter')
            ->defaultAvatarProvider(\App\Filament\Support\InitialsAvatarProvider::class)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Website'),
                NavigationGroup::make('Watch & Listen'),
                NavigationGroup::make('Programmes'),
                NavigationGroup::make('Counselling'),
                NavigationGroup::make('Giving'),
                NavigationGroup::make('Community'),
                NavigationGroup::make('System')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->widgets([AccountWidget::class, MinistryOverview::class])
            ->databaseNotifications(false)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureAccountActive::class,
            ]);
    }
}
