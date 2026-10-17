<?php

namespace App\Filament\Resources\MemberContent;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\MemberResource;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Files and links available only inside the member portal (always served privately). */
class MemberContentResource extends BaseResource
{
    protected static ?string $model = MemberResource::class;

    protected static string $permission = 'members.manage';

    protected static ?string $slug = 'member-resources';

    protected static ?string $navigationLabel = 'Member resources';

    protected static ?string $modelLabel = 'member resource';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            Fields::slug(),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            Fields::media('media_asset_id', 'File', ['document', 'audio', 'video', 'image'])->helperText('Member files stay private and are streamed only to authorised members.'),
            TextInput::make('external_url')->url()->maxLength(255),
            Select::make('access_policy')->options(['all_members' => 'All verified members', 'granted_only' => 'Only members granted access'])->default('all_members')->required(),
            Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->default('draft')->required(),
            DateTimePicker::make('published_at')->timezone(config('app.display_timezone'))->seconds(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('access_policy')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->date('j M Y'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberContents::route('/'),
            'create' => Pages\CreateMemberContent::route('/create'),
            'edit' => Pages\EditMemberContent::route('/{record}/edit'),
        ];
    }
}
