<?php

namespace App\Filament\Resources\HeroSlides;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\HeroSlide;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class HeroSlideResource extends BaseResource
{
    protected static ?string $model = HeroSlide::class;

    protected static string $permission = 'content.edit';

    protected static ?string $navigationLabel = 'Homepage slideshow';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Slide')->schema([
                Fields::media('image_asset_id', 'Background image')->required()
                    ->helperText('Use a wide (at least 1920px) authentic photograph. Avoid text inside the image.'),
                TextInput::make('sort_order')->numeric()->default(0),
                TextInput::make('duration_seconds')->label('Display time (seconds)')->numeric()->minValue(3)->maxValue(30)->default(7),
                DateTimePicker::make('starts_at')->label('Show from')->timezone(config('app.display_timezone'))->seconds(false),
                DateTimePicker::make('ends_at')->label('Show until')->timezone(config('app.display_timezone'))->seconds(false)->after('starts_at'),
            ])->columns(2),
            Fields::translations([
                TextInput::make('headline')->required()->maxLength(120)->columnSpanFull(),
                Textarea::make('description')->rows(2)->maxLength(300)->columnSpanFull(),
                TextInput::make('button_label')->maxLength(40),
                TextInput::make('button_url')->maxLength(255)->helperText('Site link such as /live, or a full https:// address.'),
                TextInput::make('secondary_label')->maxLength(40),
                TextInput::make('secondary_url')->maxLength(255),
            ], 'Slide text'),
            Fields::publishing(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('#'),
                Fields::translationColumn('headline', 'Headline'),
                Fields::statusColumn(),
                TextColumn::make('starts_at')->label('From')->dateTime('j M Y H:i', config('app.display_timezone'))->placeholder('—'),
                TextColumn::make('ends_at')->label('Until')->dateTime('j M Y H:i', config('app.display_timezone'))->placeholder('—'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeroSlides::route('/'),
            'create' => Pages\CreateHeroSlide::route('/create'),
            'edit' => Pages\EditHeroSlide::route('/{record}/edit'),
        ];
    }
}
