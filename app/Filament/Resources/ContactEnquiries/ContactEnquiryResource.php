<?php

namespace App\Filament\Resources\ContactEnquiries;

use App\Filament\Resources\BaseResource;
use App\Models\ContactEnquiry;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ContactEnquiryResource extends BaseResource
{
    protected static ?string $model = ContactEnquiry::class;

    protected static string $permission = 'enquiries.manage';

    protected static array $deniedActions = ['create'];

    protected static ?string $navigationLabel = 'Contact enquiries';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): ?string
    {
        $n = ContactEnquiry::where('status', 'new')->count();

        return $n ? (string) $n : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Enquiry')->schema([
                TextInput::make('reference')->disabled(),
                TextInput::make('name')->disabled(),
                TextInput::make('email')->disabled(),
                TextInput::make('phone')->disabled(),
                TextInput::make('subject')->disabled()->columnSpanFull(),
                Textarea::make('message')->disabled()->rows(6)->columnSpanFull(),
            ])->columns(2),
            Section::make('Handling')->schema([
                Select::make('status')->options(ContactEnquiry::STATUSES)->required(),
                Select::make('assigned_to')->label('Assigned to')->options(fn () => User::where('is_staff', true)->pluck('name', 'id'))->searchable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('subject')->searchable()->limit(50),
                TextColumn::make('status')->badge()->color(fn ($state) => $state === 'new' ? 'danger' : 'gray'),
                TextColumn::make('assignee.name')->label('Assigned')->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->options(ContactEnquiry::STATUSES)])
            ->recordActions([EditAction::make()->label('Open')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactEnquiries::route('/'),
            'edit' => Pages\EditContactEnquiry::route('/{record}/edit'),
        ];
    }
}
