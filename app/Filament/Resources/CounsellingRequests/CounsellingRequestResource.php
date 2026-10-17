<?php

namespace App\Filament\Resources\CounsellingRequests;

use App\Filament\Resources\BaseResource;
use App\Models\CounsellingRequest;
use App\Models\CounsellingService;
use App\Models\CounsellorProfile;
use App\Services\Booking\BookingException;
use App\Services\Booking\BookingService;
use App\Support\Audit;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Confidential. Access is governed by CounsellingRequestPolicy; counsellors
 * only ever query requests assigned to them.
 */
class CounsellingRequestResource extends BaseResource
{
    protected static ?string $model = CounsellingRequest::class;

    protected static array $deniedActions = ['create', 'delete', 'deleteAny'];

    protected static ?string $navigationLabel = 'Requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Counselling';

    protected static ?int $navigationSort = 1;

    protected static bool $isGloballySearchable = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['service', 'assignedCounsellor']);
        $user = auth()->user();

        if (! $user?->hasPermission('counselling.coordinate')) {
            $query->where('assigned_to', $user?->counsellorProfile?->id ?? 0);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->hasPermission('counselling.coordinate')) {
            return null;
        }
        $count = CounsellingRequest::where('status', 'new')->count();

        return $count ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Request')->schema([
                TextEntry::make('reference')->fontFamily('mono'),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => CounsellingRequest::STATUSES[$state] ?? $state),
                TextEntry::make('service.name')->placeholder('General'),
                TextEntry::make('assignedCounsellor.display_name')->label('Assigned to')->placeholder('Unassigned'),
                TextEntry::make('created_at')->dateTime('j M Y, H:i', config('app.display_timezone')),
                TextEntry::make('appointment_method')->placeholder('No preference')->formatStateUsing(fn ($state) => CounsellingService::METHODS[$state] ?? $state),
            ])->columns(3),
            Section::make('Contact')->schema([
                TextEntry::make('name')->state(fn (CounsellingRequest $r) => $r->contact_data['name'] ?? '—'),
                TextEntry::make('email')->state(fn (CounsellingRequest $r) => $r->contact_data['email'] ?? '—')->copyable(),
                TextEntry::make('phone')->state(fn (CounsellingRequest $r) => $r->contact_data['phone'] ?? '—')->copyable(),
                TextEntry::make('preferred_contact')->label('Prefers'),
            ])->columns(4),
            Section::make('Message (confidential)')->schema([
                TextEntry::make('message')->hiddenLabel()->state(fn (CounsellingRequest $r) => $r->encrypted_message)->prose(),
                TextEntry::make('preferred_times')->label('Availability')->state(fn (CounsellingRequest $r) => $r->preferred_times)->placeholder('—'),
            ]),
            Section::make('Internal notes')->schema([
                TextEntry::make('internal_notes')->hiddenLabel()->state(fn (CounsellingRequest $r) => $r->internal_notes)->placeholder('No notes yet.'),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->fontFamily('mono')->searchable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => CounsellingRequest::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'new' => 'danger', 'assigned', 'offered' => 'warning', 'scheduled' => 'success', default => 'gray' }),
                TextColumn::make('service.name')->placeholder('General'),
                TextColumn::make('assignedCounsellor.display_name')->label('Assigned to')->placeholder('—'),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(CounsellingRequest::STATUSES),
                SelectFilter::make('assigned_to')->label('Counsellor')->options(fn () => CounsellorProfile::pluck('display_name', 'id')),
            ])
            ->recordActions([ViewAction::make()]);
    }

    /** Actions shown on the request's view page. */
    public static function workflowActions(): array
    {
        $tz = config('app.display_timezone');

        return [
            Action::make('assign')->label('Assign counsellor')->icon(Heroicon::OutlinedUserPlus)
                ->visible(fn () => auth()->user()->can('assign', CounsellingRequest::class))
                ->schema([Select::make('assigned_to')->label('Counsellor')->options(fn () => CounsellorProfile::where('status', 'active')->pluck('display_name', 'id'))->required()])
                ->fillForm(fn (CounsellingRequest $record) => ['assigned_to' => $record->assigned_to])
                ->action(function (CounsellingRequest $record, array $data) {
                    $record->update(['assigned_to' => $data['assigned_to'], 'status' => $record->status === 'new' ? 'assigned' : $record->status]);
                    Audit::log('assigned', $record);
                    Notification::make()->success()->title('Counsellor assigned')->send();
                }),
            Action::make('offer')->label('Propose a time')->icon(Heroicon::OutlinedCalendar)->color('success')
                ->visible(fn (CounsellingRequest $record) => auth()->user()->can('update', $record) && $record->status !== 'closed')
                ->schema([
                    Select::make('counsellor_id')->label('Counsellor')->options(fn () => CounsellorProfile::where('status', 'active')->pluck('display_name', 'id'))->required(),
                    DateTimePicker::make('starts_at')->label('Start')->required()->timezone($tz)->seconds(false)->minDate(now()),
                    Select::make('method')->options(CounsellingService::METHODS)->required(),
                    Toggle::make('override')->label('Override availability (logged)')->helperText('Use only when you have agreed a time outside normal hours.'),
                ])
                ->fillForm(fn (CounsellingRequest $record) => ['counsellor_id' => $record->assigned_to ?? auth()->user()->counsellorProfile?->id, 'method' => $record->appointment_method ?? 'in_person'])
                ->action(function (CounsellingRequest $record, array $data) {
                    try {
                        app(BookingService::class)->offer($record, (int) $data['counsellor_id'], CarbonImmutable::parse($data['starts_at'])->utc(), $data['method'], auth()->user(), (bool) ($data['override'] ?? false));
                        Notification::make()->success()->title('Time proposed — the visitor has been emailed a link to accept it.')->send();
                    } catch (BookingException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }),
            Action::make('note')->label('Add note')->icon(Heroicon::OutlinedPencilSquare)->color('gray')
                ->visible(fn (CounsellingRequest $record) => auth()->user()->can('update', $record))
                ->schema([Textarea::make('note')->required()->rows(4)])
                ->action(function (CounsellingRequest $record, array $data) {
                    $stamp = now(config('app.display_timezone'))->format('j M Y H:i').' — '.auth()->user()->name;
                    $record->update(['internal_notes' => trim(($record->internal_notes ? $record->internal_notes."\n\n" : '').$stamp.":\n".$data['note'])]);
                    Audit::log('note_added', $record);
                }),
            Action::make('close')->label('Close request')->icon(Heroicon::OutlinedCheckCircle)->color('gray')->requiresConfirmation()
                ->visible(fn (CounsellingRequest $record) => auth()->user()->can('update', $record) && $record->status !== 'closed')
                ->action(function (CounsellingRequest $record) {
                    $record->update(['status' => 'closed', 'closed_at' => now()]);
                    Audit::log('closed', $record);
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCounsellingRequests::route('/'),
            'view' => Pages\ViewCounsellingRequest::route('/{record}'),
        ];
    }
}
