<?php

namespace App\Filament\Support;

use App\Enums\ContentStatus;
use App\Models\Language;
use App\Models\MediaAsset;
use App\Services\Media\MediaLibrary;
use App\Support\Locales;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** Reusable CMS form/table building blocks. */
class Fields
{
    /** All configured languages (enabled or not) so translations can be prepared ahead of launch. */
    public static function localeOptions(): array
    {
        return rescue(fn () => Language::query()->orderBy('sort_order')->pluck('name', 'code')->all(), ['en' => 'English'], false) ?: ['en' => 'English'];
    }

    /**
     * Translation repeater bound to the model's translations() relationship.
     * The default-language version is mandatory; others are optional.
     */
    public static function translations(array $schema, string $label = 'Content'): Repeater
    {
        $default = Locales::default();

        return Repeater::make('translations')
            ->label($label)
            ->relationship()
            ->schema([
                Select::make('locale')
                    ->options(fn () => self::localeOptions())
                    ->default($default)
                    ->required()
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan(1),
                Toggle::make('is_approved')
                    ->label('Approved for publication')
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(1),
                ...$schema,
            ])
            ->columns(2)
            ->defaultItems(1)
            ->minItems(1)
            ->addActionLabel('Add a translation')
            ->itemLabel(fn (array $state): string => (self::localeOptions()[$state['locale'] ?? ''] ?? 'New translation').(($state['is_approved'] ?? true) ? '' : ' (draft)'))
            ->collapsible()
            ->rule(fn () => function (string $attribute, $value, Closure $fail) use ($default) {
                $locales = collect($value)->pluck('locale');
                if (! $locales->contains($default)) {
                    $fail("A {$default} version is required.");
                }
            })
            ->columnSpanFull();
    }

    /** Publication workflow fields. Editors without publish rights can only draft or submit for review. */
    public static function publishing(): Section
    {
        return Section::make('Publishing')
            ->schema([
                Select::make('status')
                    ->options(function () {
                        $cases = auth()->user()?->hasPermission('content.publish') ? ContentStatus::cases() : ContentStatus::editorOptions();

                        return collect($cases)->mapWithKeys(fn (ContentStatus $s) => [$s->value => $s->getLabel()])->all();
                    })
                    ->default(ContentStatus::Draft->value)
                    ->required()
                    ->live()
                    ->helperText(fn () => auth()->user()?->hasPermission('content.publish') ? null : 'Submit for review; a publisher will publish it.'),
                DateTimePicker::make('published_at')
                    ->label(fn (Get $get) => $get('status') === ContentStatus::Scheduled->value ? 'Publish at' : 'Published at')
                    ->required(fn (Get $get) => $get('status') === ContentStatus::Scheduled->value)
                    ->timezone(config('app.display_timezone'))
                    ->seconds(false),
            ])
            ->columns(2);
    }

    public static function statusColumn(): TextColumn
    {
        return TextColumn::make('status')->badge()->sortable();
    }

    public static function translationColumn(string $field = 'title', string $label = 'Title'): TextColumn
    {
        return TextColumn::make('translated_'.$field)
            ->label($label)
            ->state(fn ($record) => $record->tr($field, Locales::default()))
            ->description(fn ($record) => count(Locales::enabled()) > 1 ? round($record->translationCompleteness() * 100).'% translated' : null)
            ->searchable(query: fn ($query, string $search) => $query->whereHas('translations', fn ($q) => $q->where($field, 'like', "%{$search}%")));
    }

    /** Pick an existing media asset or upload a new one inline. */
    public static function media(string $name, string $label, string|array $kind = 'image'): Select
    {
        $kinds = (array) $kind;
        $rule = count($kinds) === 1 ? config("fco.media.allowed.{$kinds[0]}") : null;
        $kind = $kinds;

        return Select::make($name)
            ->label($label)
            ->searchable()
            ->preload(false)
            ->getSearchResultsUsing(fn (string $search) => MediaAsset::query()->whereIn('kind', $kind)
                ->where(fn ($q) => $q->where('original_name', 'like', "%{$search}%")->orWhere('alt_text', 'like', "%{$search}%")->orWhere('caption', 'like', "%{$search}%"))
                ->latest()->limit(30)->get()->mapWithKeys(fn (MediaAsset $a) => [$a->id => self::assetLabel($a)])->all())
            ->options(fn () => MediaAsset::query()->whereIn('kind', $kind)->latest()->limit(15)->get()->mapWithKeys(fn (MediaAsset $a) => [$a->id => self::assetLabel($a)])->all())
            ->getOptionLabelUsing(fn ($value) => ($a = MediaAsset::find($value)) ? self::assetLabel($a) : null)
            ->createOptionForm([
                self::upload('file', $kind)->required(),
                TextInput::make('alt_text')->label('Alternative text')->maxLength(255)->required($kind === ['image']),
                TextInput::make('caption')->maxLength(255),
                TextInput::make('usage_permission')->label('Usage permission / credit')->maxLength(255),
            ])
            ->createOptionUsing(function (array $data) {
                $path = is_array($data['file']) ? reset($data['file']) : $data['file'];

                try {
                    return app(MediaLibrary::class)->register($path, [
                        'original_name' => basename($path),
                        'alt_text' => $data['alt_text'] ?? null,
                        'caption' => $data['caption'] ?? null,
                        'usage_permission' => $data['usage_permission'] ?? null,
                    ])->id;
                } catch (InvalidArgumentException $e) {
                    \Filament\Notifications\Notification::make()->danger()->title($e->getMessage())->send();

                    return null;
                }
            })
            ->helperText($rule ? 'Max '.round($rule['max_kb'] / 1024).' MB. Media becomes public when the content using it is published.' : null);
    }

    public static function upload(string $name, string|array $kind = 'image'): FileUpload
    {
        $rules = array_map(fn ($k) => config("fco.media.allowed.{$k}"), (array) $kind);

        return FileUpload::make($name)
            ->disk('media')
            ->directory(fn () => MediaLibrary::directory())
            ->visibility('private')
            ->acceptedFileTypes(array_merge(...array_column($rules, 'mimes')))
            ->maxSize(max(array_column($rules, 'max_kb')))
            ->getUploadedFileNameForStorageUsing(fn ($file) => Str::lower((string) Str::ulid()).'.'.strtolower($file->guessExtension() ?: $file->getClientOriginalExtension()));
    }

    public static function assetLabel(MediaAsset $asset): string
    {
        return trim(($asset->alt_text ?: $asset->original_name ?: 'Asset #'.$asset->id).' · '.$asset->humanSize().' · '.$asset->created_at?->format('j M Y'));
    }

    public static function slug(string $source = 'slug'): TextInput
    {
        return TextInput::make($source)
            ->required()
            ->maxLength(190)
            ->alphaDash()
            ->unique(ignoreRecord: true)
            ->helperText('Used in the web address. Lowercase letters, numbers and hyphens.');
    }
}
