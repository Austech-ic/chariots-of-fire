<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\BaseResource;
use App\Models\Appointment;
use App\Models\CounsellingService;
use App\Models\CounsellorProfile;
use App\Notifications\AppointmentUpdated;
use App\Services\Booking\BookingException;
use App\Services\Booking\BookingService;
use App\Support\Audit;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use UnitEnum;

class AppointmentResource extends BaseResource
{
    protected static ?string $model = Appointment::class;

    protected static array $deniedActions = ['create', 'delete', 'deleteAny'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Counselling';

    protected static ?int $navigationSort = 2;

    protected static bool $isGloballySearchable = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['service', 'counsellor']);
        $user = auth()->user();
        if (! $user?->hasPermission('counselling.coordinate')) {
            $query->where('counsellor_id', $user?->counsellorProfile?->id ?? 0);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'pending')->count();

        return $count ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');

        return $schema->components([
            Section::make()->schema([
                TextEntry::make('reference')->fontFamily('mono'),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => Appointment::STATUSES[$state] ?? $state),
                TextEntry::make('service.name'),
                TextEntry::make('counsellor.display_name')->label('Counsellor'),
                TextEntry::make('starts_at')->dateTime('l j F Y, H:i', $tz),
                TextEntry::make('method')->formatStateUsing(fn ($state) => CounsellingService::METHODS[$state] ?? $state),
                TextEntry::make('source'),
                TextEntry::make('counsellingRequest.reference')->label('From request')->placeholder('—'),
                TextEntry::make('cancellation_reason')->placeholder('—'),
            ])->columns(3),
            Section::make('Contact')->schema([
                TextEntry::make('name')->state(fn (Appointment $r) => $r->contactName() ?? '—'),
                TextEntry::make('email')->state(fn (Appointment $r) => $r->contactEmail() ?? '—')->copyable(),
                TextEntry::make('phone')->state(fn (Appointment $r) => $r->contact_data['phone'] ?? '—')->copyable(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('app.display_timezone');

        return $table
            ->defaultSort('starts_at', 'asc')
            ->columns([
                TextColumn::make('starts_at')->label('When')->dateTime('D j M Y, H:i', $tz)->sortable(),
                TextColumn::make('service.name'),
                TextColumn::make('counsellor.display_name')->label('Counsellor'),
                TextColumn::make('method')->formatStateUsing(fn ($state) => CounsellingService::METHODS[$state] ?? $state),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => Appointment::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'pending', 'offered' => 'warning', 'confirmed' => 'success', 'cancelled', 'no_show' => 'danger', default => 'gray' }),
                TextColumn::make('reference')->fontFamily('mono')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(Appointment::STATUSES)->multiple(),
                SelectFilter::make('counsellor_id')->label('Counsellor')->options(fn () => CounsellorProfile::pluck('display_name', 'id')),
                TernaryFilter::make('upcoming')->default(true)->queries(
                    true: fn ($query) => $query->where('starts_at', '>=', now()->startOfDay()),
                    false: fn ($query) => $query->where('starts_at', '<', now()->startOfDay()),
                ),
            ])
            ->recordActions([ViewAction::make(), ActionGroup::make(self::workflowActions())]);
    }

    public static function workflowActions(): array
    {
        $tz = config('app.display_timezone');

        return [
            Action::make('confirm')->icon(Heroicon::OutlinedCheck)->color('success')->requiresConfirmation()
                ->visible(fn (Appointment $record) => $record->status === 'pending' && auth()->user()->can('update', $record))
                ->action(function (Appointment $record) {
                    $record->update(['status' => 'confirmed', 'updated_by' => auth()->id()]);
                    if ($email = $record->contactEmail()) {
                        NotificationFacade::route('mail', $email)->notify(new AppointmentUpdated($record, 'confirmed'));
                    }
                }),
            Action::make('reschedule')->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (Appointment $record) => $record->isChangeable() && auth()->user()->can('update', $record))
                ->schema([
                    DateTimePicker::make('starts_at')->label('New start')->required()->timezone($tz)->seconds(false)->minDate(now()),
                    Toggle::make('override')->label('Override availability (logged)'),
                ])
                ->action(function (Appointment $record, array $data) {
                    try {
                        app(BookingService::class)->reschedule($record, CarbonImmutable::parse($data['starts_at'])->utc(), auth()->user(), (bool) ($data['override'] ?? false));
                        Notification::make()->success()->title('Rescheduled and the visitor notified.')->send();
                    } catch (BookingException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }),
            Action::make('cancel')->icon(Heroicon::OutlinedXMark)->color('danger')
                ->visible(fn (Appointment $record) => in_array($record->status, Appointment::BLOCKING, true) && auth()->user()->can('update', $record))
                ->schema([Textarea::make('reason')->rows(2)])
                ->action(fn (Appointment $record, array $data) => app(BookingService::class)->cancel($record, $data['reason'] ?? 'Cancelled by staff', auth()->user())),
            Action::make('completed')->label('Mark completed')->icon(Heroicon::OutlinedCheckBadge)
                ->visible(fn (Appointment $record) => $record->status === 'confirmed' && $record->starts_at->isPast() && auth()->user()->can('update', $record))
                ->action(fn (Appointment $record) => $record->update(['status' => 'completed', 'updated_by' => auth()->id()])),
            Action::make('noShow')->label('Mark no-show')->icon(Heroicon::OutlinedUserMinus)->color('warning')
                ->visible(fn (Appointment $record) => $record->status === 'confirmed' && $record->starts_at->isPast() && auth()->user()->can('update', $record))
                ->action(function (Appointment $record) {
                    $record->update(['status' => 'no_show', 'updated_by' => auth()->id()]);
                    Audit::log('no_show', $record);
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'view' => Pages\ViewAppointment::route('/{record}'),
        ];
    }
}
