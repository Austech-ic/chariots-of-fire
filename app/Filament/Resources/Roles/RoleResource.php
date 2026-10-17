<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\BaseResource;
use App\Models\Permission;
use App\Models\Role;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RoleResource extends BaseResource
{
    protected static ?string $model = Role::class;

    protected static string $permission = 'users.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    /** Role definitions can grant any access, so only super-admins may change them. */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?\Illuminate\Database\Eloquent\Model $record = null): \Illuminate\Auth\Access\Response
    {
        return auth()->user()?->isSuperAdmin() ? parent::getAuthorizationResponse($action, $record) : \Illuminate\Auth\Access\Response::deny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true)->disabled(fn (?Role $record) => $record?->slug === 'super-admin'),
                TextInput::make('description')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Permissions')->schema([
                CheckboxList::make('permissions')->relationship('permissions', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Permission $p) => "{$p->group}: {$p->name}")
                    ->columns(2)->bulkToggleable()->hiddenLabel(),
            ])->visible(fn (?Role $record) => $record?->slug !== 'super-admin'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount(['users', 'permissions']))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('slug')->color('gray'),
                TextColumn::make('permissions_count')->label('Permissions')->formatStateUsing(fn ($state, Role $r) => $r->slug === 'super-admin' ? 'All' : $state),
                TextColumn::make('users_count')->label('Users'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
