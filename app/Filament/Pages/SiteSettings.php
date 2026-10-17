<?php

namespace App\Filament\Pages;

use App\Support\Audit;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                Tab::make('Identity')->schema([
                    TextInput::make('site_name')->required()->maxLength(120),
                    TextInput::make('tagline')->maxLength(160),
                    Textarea::make('footer_text')->rows(2)->maxLength(300),
                ]),
                Tab::make('Contact & social')->schema([
                    Section::make()->schema([
                        TextInput::make('contact_email')->email(),
                        TextInput::make('contact_phone')->tel(),
                        TextInput::make('whatsapp_number')->tel()->helperText('International format, e.g. +2348000000000'),
                        Textarea::make('contact_address')->rows(2),
                    ])->columns(2),
                    Section::make('Social media')->schema([
                        TextInput::make('social_youtube')->url(),
                        TextInput::make('social_facebook')->url(),
                        TextInput::make('social_instagram')->url(),
                        TextInput::make('social_x')->label('X (Twitter)')->url(),
                    ])->columns(2),
                ]),
                Tab::make('Counselling')->schema([
                    Textarea::make('counselling_intro')->label('Introduction')->rows(3),
                    Textarea::make('counselling_confidentiality')->label('Confidentiality statement')->rows(4),
                ]),
                Tab::make('Giving')->schema([
                    Textarea::make('giving_intro')->label('Introduction')->rows(3),
                    TextInput::make('donation_presets')->label('Preset amounts')->helperText('Comma-separated, in major units, e.g. 5000,10000,25000'),
                    TextInput::make('donation_currencies')->label('Currencies offered')->helperText('Comma-separated ISO codes. Only codes your payment provider supports are shown.'),
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
        Audit::log('settings_updated', null, ['keys' => array_keys($data)]);
        Notification::make()->success()->title('Settings saved')->send();
    }
}
