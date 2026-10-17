<?php

namespace App\Filament\Resources\Members;

use App\Filament\Resources\BaseResource;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/** Member (non-staff) accounts. */
class MemberAccountResource extends BaseResource
{
    protected static ?string $model = User::class;

    protected static string $permission = 'members.manage';

    protected static array $deniedActions = ['create', 'delete', 'deleteAny'];

    protected static ?string $slug = 'members';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'member';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_staff', false);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->email()->disabled(),
            TextInput::make('phone')->tel()->maxLength(32),
            Select::make('status')->options([User::STATUS_ACTIVE => 'Active', User::STATUS_SUSPENDED => 'Suspended', User::STATUS_DEACTIVATED => 'Deactivated'])->required(),
            Select::make('resourceGrants')->label('Restricted resources granted')->relationship('resourceGrants', 'title', fn ($q) => $q->where('access_policy', 'granted_only'))
                ->multiple()->preload()->pivotData(['granted_at' => now()])->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                IconColumn::make('email_verified_at')->label('Verified')->boolean(),
                TextColumn::make('status')->badge()->color(fn ($state) => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('last_login_at')->since()->placeholder('Never'),
                TextColumn::make('created_at')->label('Joined')->date('j M Y')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options([User::STATUS_ACTIVE => 'Active', User::STATUS_SUSPENDED => 'Suspended', User::STATUS_DEACTIVATED => 'Deactivated'])])
            ->recordActions([
                Action::make('suspend')->icon(Heroicon::OutlinedLockClosed)->color('danger')->requiresConfirmation()
                    ->visible(fn (User $r) => $r->isActive())
                    ->action(fn (User $r) => self::setStatus($r, User::STATUS_SUSPENDED)),
                Action::make('reactivate')->icon(Heroicon::OutlinedLockOpen)->color('success')->requiresConfirmation()
                    ->visible(fn (User $r) => ! $r->isActive())
                    ->action(fn (User $r) => self::setStatus($r, User::STATUS_ACTIVE)),
                EditAction::make(),
            ]);
    }

    /** Suspension also revokes every active session and remember-me token. */
    public static function setStatus(User $user, string $status): void
    {
        $user->forceFill([
            'status' => $status,
            'suspended_at' => $status === User::STATUS_ACTIVE ? null : now(),
            'remember_token' => $status === User::STATUS_ACTIVE ? $user->remember_token : \Illuminate\Support\Str::random(60),
        ])->save();

        if ($status !== User::STATUS_ACTIVE && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
        Audit::log('status_changed', $user, ['status' => $status]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberAccounts::route('/'),
            'edit' => Pages\EditMemberAccount::route('/{record}/edit'),
        ];
    }
}
