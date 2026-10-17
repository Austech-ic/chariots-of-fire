<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Members\MemberAccountResource;
use App\Models\User;
use App\Support\Audit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use UnitEnum;

class StaffUserResource extends BaseResource
{
    protected static ?string $model = User::class;

    protected static string $permission = 'users.manage';

    protected static array $deniedActions = ['delete', 'deleteAny'];

    protected static ?string $slug = 'staff';

    protected static ?string $navigationLabel = 'Staff accounts';

    protected static ?string $modelLabel = 'staff member';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_staff', true)->with('roles');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->tel()->maxLength(32),
            Select::make('roles')->relationship('roles', 'name')->multiple()->preload()
                // Only super-admins may grant roles carrying confidential (counselling) or financial access.
                ->disableOptionWhen(fn ($value) => ! auth()->user()->isSuperAdmin() && self::isSensitiveRole((int) $value))
                ->helperText('Counselling and finance access are separate roles. Grant only what each person needs.'),
            Select::make('status')->options([User::STATUS_ACTIVE => 'Active', User::STATUS_SUSPENDED => 'Suspended'])->default(User::STATUS_ACTIVE)->required(),
        ])->columns(2);
    }

    public static function isSensitiveRole(int $roleId): bool
    {
        $role = \App\Models\Role::with('permissions')->find($roleId);

        return ! $role || $role->slug === 'super-admin'
            || $role->permissions->contains(fn ($p) => str_starts_with($p->slug, 'counselling.') || str_starts_with($p->slug, 'donations.') || $p->slug === 'users.manage');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('roles.name')->badge(),
                IconColumn::make('two_factor')->label('2FA')->boolean()->state(fn (User $r) => filled($r->app_authentication_secret)),
                TextColumn::make('status')->badge()->color(fn ($state) => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('last_login_at')->since()->placeholder('Never'),
            ])
            ->recordActions([
                Action::make('suspend')->icon(Heroicon::OutlinedLockClosed)->color('danger')->requiresConfirmation()
                    ->visible(fn (User $r) => $r->isActive() && $r->id !== auth()->id())
                    ->action(fn (User $r) => MemberAccountResource::setStatus($r, User::STATUS_SUSPENDED)),
                Action::make('reactivate')->icon(Heroicon::OutlinedLockOpen)->color('success')
                    ->visible(fn (User $r) => ! $r->isActive())
                    ->action(fn (User $r) => MemberAccountResource::setStatus($r, User::STATUS_ACTIVE)),
                Action::make('sendReset')->label('Send password link')->icon(Heroicon::OutlinedKey)->color('gray')
                    ->action(fn (User $r) => Password::sendResetLink(['email' => $r->email])),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffUsers::route('/'),
            'create' => Pages\CreateStaffUser::route('/create'),
            'edit' => Pages\EditStaffUser::route('/{record}/edit'),
        ];
    }
}
