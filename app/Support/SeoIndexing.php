<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoIndexing
{
    public static function robotsFor(Model $model, ?string $default = null): ?string
    {
        $explicit = SeoMetadata::robots($model);
        if ($explicit) {
            return $explicit;
        }

        if ($model instanceof Category) {
            $definition = SeoPageRegistry::definitionForCategorySlug($model->slug);
            if ($definition && ! self::definitionIndexable($definition)) {
                return 'noindex,follow';
            }

            if (! $definition && ! SolarFloodLightSeoCatalog::isSolarCategory($model)) {
                return 'noindex,follow';
            }
        }

        if ($model instanceof Page) {
            $definition = SeoPageRegistry::definitionForPageSlug($model->slug);
            if ($definition && ! self::definitionIndexable($definition)) {
                return 'noindex,follow';
            }
        }

        if ($model instanceof Product && ! ProductSeo::isSolarLightingProduct($model)) {
            return 'noindex,follow';
        }

        return $default;
    }

    public static function sitemapEligible(Model $model): bool
    {
        if (self::sitemapDisabled($model)) {
            return false;
        }

        $robots = self::robotsFor($model);
        if ($robots && Str::contains(Str::lower($robots), 'noindex')) {
            return false;
        }

        if ($model instanceof Category) {
            $definition = SeoPageRegistry::definitionForCategorySlug($model->slug);

            return ! $definition || self::definitionIndexable($definition);
        }

        if ($model instanceof Page) {
            $definition = SeoPageRegistry::definitionForPageSlug($model->slug);

            return ! $definition || self::definitionIndexable($definition);
        }

        return true;
    }

    public static function definitionIndexable(array $definition): bool
    {
        if (($definition['status'] ?? 'planned') !== 'published') {
            return false;
        }

        if (! (bool) ($definition['indexable'] ?? false)) {
            return false;
        }

        if (SeoPageRegistry::requiresProducts($definition) && ! SeoPageRegistry::hasProducts($definition)) {
            return false;
        }

        return true;
    }

    private static function sitemapDisabled(Model $model): bool
    {
        if (! method_exists($model, 'getTable') || ! Schema::hasColumn($model->getTable(), 'sitemap_enabled')) {
            return false;
        }

        return $model->getAttribute('sitemap_enabled') === false
            || (string) $model->getAttribute('sitemap_enabled') === '0';
    }
}
