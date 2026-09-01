<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoDuplicateGuard
{
    public static function normalizeIntent(?string $value): string
    {
        $text = Str::ascii(Str::lower(strip_tags((string) $value)));
        $text = preg_replace('/\b(\d+)\s*(w|watt|watts)\b/u', '$1w', $text) ?? $text;
        $text = preg_replace('/\blights\b/u', 'light', $text) ?? $text;
        $text = preg_replace('/\b(prices)\b/u', 'price', $text) ?? $text;
        $text = preg_replace('/\b(in|the|a|an|of|for|and|with)\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * @param  array{primary_keyword?: ?string, seo_title?: ?string, h1?: ?string, slug?: ?string}  $attributes
     * @return array<int, string>
     */
    public static function conflicts(array $attributes, ?Model $except = null): array
    {
        $conflicts = [];
        $keyword = trim((string) ($attributes['primary_keyword'] ?? ''));
        $title = trim((string) ($attributes['seo_title'] ?? ''));
        $h1 = trim((string) ($attributes['h1'] ?? ''));
        $slug = Str::slug((string) ($attributes['slug'] ?? ''));

        if ($keyword !== '') {
            $normalizedKeyword = self::normalizeIntent($keyword);
            foreach (self::contentModels() as $modelClass => $label) {
                if (! self::columnReady($modelClass, 'primary_keyword')) {
                    continue;
                }

                $modelClass::query()
                    ->whereNotNull('primary_keyword')
                    ->get(['id', 'primary_keyword'])
                    ->each(function (Model $model) use (&$conflicts, $except, $normalizedKeyword, $label): void {
                        if (self::isSameModel($model, $except)) {
                            return;
                        }

                        if (self::normalizeIntent((string) $model->getAttribute('primary_keyword')) === $normalizedKeyword) {
                            $conflicts[] = 'Primary keyword overlaps an existing '.$label.' record.';
                        }
                    });
            }

            if (Schema::hasTable('seo_pages') && self::registryQuery($except)->where('primary_keyword_normalized', $normalizedKeyword)->exists()) {
                $conflicts[] = 'Primary keyword already exists in the SEO page registry.';
            }
        }

        if ($title !== '') {
            foreach (self::contentModels() as $modelClass => $label) {
                if (! self::columnReady($modelClass, 'seo_title')) {
                    continue;
                }

                $exists = $modelClass::query()
                    ->whereRaw('LOWER(seo_title) = ?', [Str::lower($title)])
                    ->when($except && $except instanceof $modelClass, fn ($query) => $query->whereKeyNot($except->getKey()))
                    ->exists();

                if ($exists) {
                    $conflicts[] = 'SEO title already exists on another '.$label.' record.';
                }
            }

            if (Schema::hasTable('seo_pages') && self::registryQuery($except)->whereRaw('LOWER(seo_title) = ?', [Str::lower($title)])->exists()) {
                $conflicts[] = 'SEO title already exists in the SEO page registry.';
            }
        }

        if ($h1 !== '') {
            foreach ([Category::class => 'category', Page::class => 'page', Product::class => 'product'] as $modelClass => $label) {
                $column = $modelClass === Page::class ? 'title' : 'name';
                if (! self::columnReady($modelClass, $column)) {
                    continue;
                }

                $exists = $modelClass::query()
                    ->whereRaw('LOWER('.$column.') = ?', [Str::lower($h1)])
                    ->when($except && $except instanceof $modelClass, fn ($query) => $query->whereKeyNot($except->getKey()))
                    ->exists();

                if ($exists) {
                    $conflicts[] = 'H1/title already exists on another '.$label.' record.';
                }
            }

            if (Schema::hasTable('seo_pages') && self::registryQuery($except)->whereRaw('LOWER(h1) = ?', [Str::lower($h1)])->exists()) {
                $conflicts[] = 'H1 already exists in the SEO page registry.';
            }
        }

        if ($slug !== '') {
            foreach ([Category::class => 'category', Page::class => 'page'] as $modelClass => $label) {
                if (! self::columnReady($modelClass, 'slug')) {
                    continue;
                }

                $exists = $modelClass::query()
                    ->whereRaw('LOWER(slug) = ?', [Str::lower($slug)])
                    ->when($except && $except instanceof $modelClass, fn ($query) => $query->whereKeyNot($except->getKey()))
                    ->exists();

                if ($exists) {
                    $conflicts[] = 'Slug already exists on another '.$label.' record.';
                }
            }
        }

        return array_values(array_unique($conflicts));
    }

    /**
     * @return array<class-string<Model>, string>
     */
    private static function contentModels(): array
    {
        return [
            Category::class => 'category',
            Product::class => 'product',
            Page::class => 'page',
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function columnReady(string $modelClass, string $column): bool
    {
        /** @var Model $model */
        $model = new $modelClass;

        return Schema::hasTable($model->getTable()) && Schema::hasColumn($model->getTable(), $column);
    }

    private static function isSameModel(Model $model, ?Model $except): bool
    {
        return $except
            && $model->getTable() === $except->getTable()
            && (string) $model->getKey() === (string) $except->getKey();
    }

    private static function registryQuery(?Model $except)
    {
        return SeoPage::query()
            ->when($except && $except->exists, function ($query) use ($except): void {
                $query->where(function ($registryQuery) use ($except): void {
                    $registryQuery->whereNull('content_model_type')
                        ->orWhere('content_model_type', '!=', $except::class)
                        ->orWhere('content_model_id', '!=', $except->getKey());
                });
            });
    }
}
