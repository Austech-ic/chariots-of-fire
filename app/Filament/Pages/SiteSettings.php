<?php

namespace App\Filament\Pages;

use App\Filament\Support\Fields;
use App\Models\MediaAsset;
use App\Models\Page as CmsPage;
use App\Support\Audit;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SiteSettings extends Page
{
    protected string $view = 'filament.pages.site-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Site settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasPermission('settings.manage');
    }

    public function mount(): void
    {
        $this->form->fill(array_intersect_key(Settings::all(), Settings::DEFAULTS));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Tabs::make()->tabs([
                Tab::make('Identity & logo')->schema([
                    Section::make('Name')->schema([
                        TextInput::make('site_name')->required()->maxLength(120),
                        TextInput::make('tagline')->maxLength(160),
                        Textarea::make('footer_text')->rows(2)->maxLength(300)->columnSpanFull(),
                    ])->columns(2),
                    Section::make('Logo')
                        ->description('PNG or WebP with a transparent background works best (SVG is not accepted for security reasons). Leave empty to use the built-in flame mark.')
                        ->schema([
                            Fields::media('logo_asset_id', 'Logo for light backgrounds')
                                ->helperText('Shown in the header in light mode. Use a dark-coloured logo.'),
                            Fields::media('logo_dark_asset_id', 'Logo for dark backgrounds')
                                ->helperText('Shown in dark mode, over photos and in the footer. Use a white/light logo. If empty, the first logo is used.'),
                            TextInput::make('logo_height')->label('Logo height')->numeric()->minValue(24)->maxValue(80)->suffix('px')->default(40),
                            Toggle::make('show_site_name')->label('Show the site name next to the logo')->inline(false)
                                ->formatStateUsing(fn ($state) => $state === null || $state === '1' || $state === true)
                                ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0'),
                        ])->columns(2),
                    Section::make('Browser and sharing')->schema([
                        Fields::media('favicon_asset_id', 'Favicon (browser tab icon)')
                            ->helperText('A square PNG, at least 192×192 px.'),
                        Fields::media('og_image_asset_id', 'Default share image')
                            ->helperText('Used when pages are shared on WhatsApp, Facebook or X and have no image of their own. 1200×630 px recommended.'),
                        Textarea::make('seo_description')->label('Default search description')->rows(2)->maxLength(160)->columnSpanFull()
                            ->helperText('Shown by Google for pages without their own summary. Up to 160 characters.'),
                    ])->columns(2),
                    Section::make('Announcement bar')->schema([
                        TextInput::make('announcement_text')->label('Message')->maxLength(160)
                            ->helperText('Shown in a slim bar above the header on every page. Leave empty to hide it.'),
                        TextInput::make('announcement_url')->label('Link (optional)')->maxLength(255)
                            ->helperText('Site link such as /events, or a full https:// address.'),
                    ])->columns(2),
                ]),
                Tab::make('Contact & social')->schema([
                    Section::make()->schema([
                        TextInput::make('contact_email')->email(),
                        TextInput::make('contact_phone')->tel(),
                        TextInput::make('whatsapp_number')->tel()->helperText('International format, e.g. +2348000000000'),
                        Textarea::make('contact_address')->rows(2),
                        Textarea::make('service_times')->label('Service and meeting times')->rows(3)
                            ->helperText('One per line, e.g. “Sunday Service — 9:00 am”. Shown in the footer and on the contact page.'),
                        TextInput::make('map_embed_url')->label('Google Maps embed link')->url()
                            ->helperText('Google Maps → Share → Embed a map → copy only the https://www.google.com/maps/embed?... address.')
                            ->rule(fn () => function ($attribute, $value, $fail) {
                                if ($value && ! str_starts_with($value, 'https://www.google.com/maps/embed')) {
                                    $fail('Paste the https://www.google.com/maps/embed?... link from Google Maps.');
                                }
                            }),
                    ])->columns(2),
                    Section::make('Social media')->schema([
                        TextInput::make('social_youtube')->url(),
                        TextInput::make('social_facebook')->url(),
                        TextInput::make('social_instagram')->url(),
                        TextInput::make('social_x')->label('X (Twitter)')->url(),
                        TextInput::make('social_tiktok')->label('TikTok')->url(),
                        TextInput::make('social_telegram')->label('Telegram')->url(),
                    ])->columns(2),
                ]),
                Tab::make('Menu')->schema([
                    Select::make('menu_page_ids')->label('Extra pages in the main menu')
                        ->helperText('Published pages chosen here appear under “About” in the header, in this order. Pages can also be listed in the footer from the page itself.')
                        ->multiple()->searchable()
                        ->options(fn () => CmsPage::query()->with('translations')->get()->mapWithKeys(fn (CmsPage $p) => [$p->id => ($p->tr('title') ?? $p->slug).($p->isPubliclyVisible() ? '' : ' (not published)')])->all())
                        ->formatStateUsing(fn ($state) => array_values(array_filter(array_map('intval', is_array($state) ? $state : explode(',', (string) $state)))))
                        ->dehydrateStateUsing(fn ($state) => implode(',', array_map('intval', (array) $state)) ?: null),
                ]),
                Tab::make('Counselling')->schema([
                    Textarea::make('counselling_intro')->label('Introduction')->rows(3),
                    Textarea::make('counselling_confidentiality')->label('Confidentiality statement')->rows(4),
                ]),
                Tab::make('Giving')->schema([
                    Textarea::make('giving_intro')->label('Introduction')->rows(3),
                    TextInput::make('donation_presets')->label('Preset amounts')->helperText('Comma-separated, in major units, e.g. 5000,10000,25000'),
                    TextInput::make('donation_currencies')->label('Currencies offered')->helperText('Comma-separated ISO codes. Only codes your payment provider supports are shown.'),
                    Textarea::make('bank_transfer_details')->label('Bank transfer details')->rows(4)
                        ->helperText('Optional. Account name, bank and account number for people who prefer to give by transfer. Shown on the Give page.'),
                ]),
            ])->persistTabInQueryString(),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            if (array_key_exists($key, Settings::DEFAULTS)) {
                Settings::set($key, $value);
            }
        }
        // Logos, favicon and share image are shown publicly.
        $mediaIds = array_filter(array_map(fn ($k) => $data[$k] ?? null, Settings::MEDIA_KEYS));
        if ($mediaIds) {
            MediaAsset::query()->whereKey($mediaIds)->update(['visibility' => 'public']);
        }
        Settings::flush();
        Audit::log('settings_updated', null, ['keys' => array_keys($data)]);
        Notification::make()->success()->title('Settings saved')->send();
    }
}
