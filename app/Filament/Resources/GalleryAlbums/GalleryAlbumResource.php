<?php

namespace App\Filament\Resources\GalleryAlbums;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\Event;
use App\Models\GalleryAlbum;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class GalleryAlbumResource extends BaseResource
{
    protected static ?string $model = GalleryAlbum::class;

    protected static string $permission = 'gallery.manage';

    protected static ?string $navigationLabel = 'Gallery albums';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Programmes';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Album')->schema([
                Fields::slug(),
                DatePicker::make('taken_at')->label('Date'),
                TextInput::make('category')->maxLength(60)->datalist(fn () => GalleryAlbum::query()->whereNotNull('category')->distinct()->pluck('category')->all()),
                Select::make('event_id')->label('Related programme')
                    ->options(fn () => Event::with('translations')->latest('starts_at')->limit(100)->get()->mapWithKeys(fn ($e) => [$e->id => $e->tr('title')]))
                    ->searchable(),
                Fields::media('cover_asset_id', 'Cover image')->helperText('Defaults to the first photo.'),
                TagsInput::make('video_urls')->label('YouTube / Vimeo links')->placeholder('Paste a link and press Enter'),
            ])->columns(2),
            Fields::translations([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->rows(2)->columnSpanFull(),
            ]),
            Section::make('Photos')
                ->description('Use “Bulk upload photos” at the top of the page to add many photos at once. Drag to reorder.')
                ->schema([
                    Repeater::make('images')->relationship()->hiddenLabel()->orderColumn('sort_order')
                        ->schema([
                            Fields::media('media_asset_id', 'Photo')->required(),
                            TextInput::make('alt_text')->label('Alternative text')->maxLength(255),
                            TextInput::make('caption')->maxLength(255),
                        ])->columns(3)->defaultItems(0)->collapsible()->addActionLabel('Add photo'),
                ])->visibleOn('edit'),
            Fields::publishing(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations')->withCount('images'))
            ->defaultSort('taken_at', 'desc')
            ->columns([
                Fields::translationColumn(),
                TextColumn::make('taken_at')->date('j M Y')->sortable(),
                TextColumn::make('images_count')->label('Photos'),
                TextColumn::make('category'),
                Fields::statusColumn(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleryAlbums::route('/'),
            'create' => Pages\CreateGalleryAlbum::route('/create'),
            'edit' => Pages\EditGalleryAlbum::route('/{record}/edit'),
        ];
    }
}
