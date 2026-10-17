<?php

namespace App\Filament\Resources\HomeSections;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\HomeSection;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Order, visibility and wording of homepage sections. */
class HomeSectionResource extends BaseResource
{
    protected static ?string $model = HomeSection::class;

    protected static string $permission = 'content.edit';

    protected static array $deniedActions = ['create', 'delete', 'deleteAny'];

    protected static ?string $navigationLabel = 'Homepage sections';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('key')->disabled()->formatStateUsing(fn ($state) => HomeSection::KEYS[$state] ?? $state),
                Toggle::make('is_visible')->label('Visible on homepage')->inline(false),
            ])->columns(2),
            Fields::translations([
                TextInput::make('eyebrow')->maxLength(60),
                TextInput::make('heading')->required()->maxLength(160),
                Textarea::make('body')->rows(3)->columnSpanFull(),
                TextInput::make('link_label')->maxLength(60),
                TextInput::make('link_url')->maxLength(255),
            ], 'Section text'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('key')->label('Section')->formatStateUsing(fn ($state) => HomeSection::KEYS[$state] ?? $state),
                Fields::translationColumn('heading', 'Heading'),
                ToggleColumn::make('is_visible')->label('Visible'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeSections::route('/'),
            'edit' => Pages\EditHomeSection::route('/{record}/edit'),
        ];
    }
}
