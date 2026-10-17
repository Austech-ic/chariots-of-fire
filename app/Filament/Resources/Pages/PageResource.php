<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends BaseResource
{
    protected static ?string $model = Page::class;

    protected static string $permission = 'content.edit';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 10;

    public const TYPES = ['standard' => 'Standard page', 'about' => 'About the ministry', 'founder' => 'Founder biography', 'legal' => 'Legal / policy'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page')->schema([
                Fields::slug(),
                Select::make('type')->options(self::TYPES)->default('standard')->required()
                    ->helperText('“About” and “Founder” pages power /about and /founder.'),
                Fields::media('image_asset_id', 'Banner / portrait image'),
                Toggle::make('show_in_footer')->inline(false),
            ])->columns(2),
            Fields::translations([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('summary')->rows(2)->maxLength(500)->columnSpanFull(),
                RichEditor::make('body')->columnSpanFull(),
                TextInput::make('meta_title')->label('SEO title')->maxLength(255),
                TextInput::make('meta_description')->label('SEO description')->maxLength(300),
            ]),
            Fields::publishing(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->columns([
                Fields::translationColumn(),
                TextColumn::make('slug')->color('gray'),
                TextColumn::make('type')->formatStateUsing(fn ($state) => self::TYPES[$state] ?? $state),
                Fields::statusColumn(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([SelectFilter::make('type')->options(self::TYPES)])
            ->recordActions([
                Action::make('preview')->icon(Heroicon::OutlinedEye)->color('gray')
                    ->url(fn (Page $r) => match ($r->type) { 'about' => lroute('about'), 'founder' => lroute('founder'), default => lroute('pages.show', $r) })
                    ->openUrlInNewTab()->visible(fn (Page $r) => $r->isPubliclyVisible()),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
