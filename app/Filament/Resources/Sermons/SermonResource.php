<?php

namespace App\Filament\Resources\Sermons;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\Sermon;
use App\Models\SermonSeries;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class SermonResource extends BaseResource
{
    protected static ?string $model = Sermon::class;

    protected static string $permission = 'sermons.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMicrophone;

    protected static string|UnitEnum|null $navigationGroup = 'Watch & Listen';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sermon')->schema([
                Fields::slug(),
                DatePicker::make('preached_at')->label('Date preached')->required()->default(now()),
                Select::make('speakers')->relationship('speakers', 'name')->multiple()->preload()->searchable()
                    ->createOptionForm([
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('slug')->required()->alphaDash()->unique('speakers', 'slug'),
                    ]),
                Select::make('categories')->label('Topics')->relationship('categories', 'slug')->multiple()->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->tr('name') ?? $record->slug),
                Select::make('series_id')->label('Series')
                    ->options(fn () => SermonSeries::with('translations')->get()->mapWithKeys(fn ($s) => [$s->id => $s->tr('title') ?? $s->slug]))
                    ->searchable(),
                Select::make('language')->options(fn () => Fields::localeOptions())->default(Locales::default())->required(),
                Fields::media('thumbnail_asset_id', 'Thumbnail'),
                Toggle::make('featured')->inline(false),
                Toggle::make('members_only')->label('Members only')->inline(false)->helperText('Only signed-in members can play it; files stay private.'),
            ])->columns(2),

            Fields::translations([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('scripture_references')->maxLength(255)->columnSpanFull()->placeholder('e.g. John 3:16; Romans 8:1-11'),
                Textarea::make('description')->rows(5)->columnSpanFull(),
            ]),

            Section::make('YouTube / video links')->description('Link a YouTube or Vimeo video instead of uploading a copy.')->schema([
                Repeater::make('externalSources')->relationship()->hiddenLabel()
                    ->schema([
                        Select::make('provider')->options(['youtube' => 'YouTube', 'vimeo' => 'Vimeo'])->default('youtube')->required(),
                        TextInput::make('external_id')->label('Video URL or ID')->required()->maxLength(255)
                            ->rule(fn ($get) => function ($attr, $value, $fail) use ($get) {
                                if (! app(\App\Services\Streaming\StreamManager::class)->resolve($get('provider'), (string) $value)) {
                                    $fail('That does not look like a valid '.ucfirst((string) $get('provider')).' link.');
                                }
                            }),
                        Toggle::make('is_embeddable')->label('Can be embedded')->default(true)->inline(false)
                            ->helperText('Turn off if the owner disabled embedding; visitors get a link instead.'),
                    ])->columns(3)->defaultItems(0)->addActionLabel('Add video link'),
            ]),

            Section::make('Uploaded audio, video and documents')->schema([
                Repeater::make('media')->relationship()->hiddenLabel()->orderColumn('sort_order')
                    ->schema([
                        Select::make('media_type')->options(['audio' => 'Audio', 'video' => 'Video', 'document' => 'Notes (PDF)'])->default('audio')->required(),
                        Fields::media('media_asset_id', 'File', ['audio', 'video', 'document'])->required(),
                        Toggle::make('download_allowed')->label('Allow download')->inline(false),
                    ])->columns(3)->defaultItems(0)->addActionLabel('Add file'),
            ]),

            Fields::publishing(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['translations', 'speakers']))
            ->defaultSort('preached_at', 'desc')
            ->columns([
                Fields::translationColumn(),
                TextColumn::make('speakers.name')->label('Speaker')->listWithLineBreaks()->limitList(2),
                TextColumn::make('preached_at')->date('j M Y')->sortable(),
                IconColumn::make('featured')->boolean(),
                IconColumn::make('members_only')->label('Members')->boolean(),
                Fields::statusColumn(),
            ])
            ->filters([
                SelectFilter::make('status')->options(\App\Enums\ContentStatus::class),
                SelectFilter::make('speakers')->relationship('speakers', 'name')->multiple(),
                TernaryFilter::make('featured'),
            ])
            ->recordActions([
                Action::make('view')->icon(Heroicon::OutlinedEye)->color('gray')->url(fn (Sermon $r) => lroute('sermons.show', $r))
                    ->openUrlInNewTab()->visible(fn (Sermon $r) => $r->isPubliclyVisible()),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSermons::route('/'),
            'create' => Pages\CreateSermon::route('/create'),
            'edit' => Pages\EditSermon::route('/{record}/edit'),
        ];
    }
}
