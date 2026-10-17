<?php

namespace App\Models\Concerns;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Editorial content stored in a sibling "<model>_translations" table.
 *
 * Models declare `protected string $translationModel` and optionally
 * `protected string $translationForeignKey`.
 */
trait HasTranslations
{
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel, $this->translationForeignKey ?? $this->getForeignKey());
    }

    /** Approved translation for the locale, falling back to the default locale. */
    public function translation(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();
        $translations = $this->relationLoaded('translations') ? $this->translations : $this->translations()->get();
        $approved = $translations->where('is_approved', true);

        return $approved->firstWhere('locale', $locale)
            ?? $approved->firstWhere('locale', Locales::default())
            ?? $approved->first();
    }

    public function tr(string $field, ?string $locale = null): ?string
    {
        return $this->translation($locale)?->{$field};
    }

    public function hasTranslation(string $locale): bool
    {
        return $this->translations->where('locale', $locale)->where('is_approved', true)->isNotEmpty();
    }

    /** Fraction of enabled locales with an approved translation (0..1). */
    public function translationCompleteness(): float
    {
        $locales = Locales::enabled();
        if ($locales === []) {
            return 1.0;
        }

        $done = $this->translations->where('is_approved', true)->pluck('locale')->intersect($locales)->count();

        return $done / count($locales);
    }

    public function scopeWithTranslations(Builder $query): Builder
    {
        return $query->with('translations');
    }

    public function scopeWhereTranslation(Builder $query, string $field, string $operator, mixed $value): Builder
    {
        return $query->whereHas('translations', fn (Builder $q) => $q->where($field, $operator, $value));
    }
}
