<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoMetadata
{
    public static function homepageTitle(int $page = 1): string
    {
        $title = 'Solar Flood Lights Kenya | Best Prices & Fast Delivery';

        return $page > 1 ? $title.' - Page '.$page : $title;
    }

    public static function homepageDescription(): string
    {
        return 'Shop solar flood lights in Kenya for homes, compounds, farms, security and business lighting. Compare prices, wattages and delivery options.';
    }

    public static function categoryTitle(Category $category, int $page = 1): string
    {
        $categoryName = $category->name;
        $mappedTitle = SolarFloodLightSeoCatalog::categoryTitles()[Str::slug($category->slug)] ?? null;
        $categoryTitle = $mappedTitle
            ?: (Str::contains(Str::lower($categoryName), ['kenya', 'price'])
                ? $categoryName.' | Solar Flood Lights Kenya'
                : $categoryName.' in Kenya | Solar Flood Lights Kenya');

        $title = self::columnValue($category, 'seo_title')
            ?: (SolarFloodLightSeoCatalog::isPriceAuthorityCategory($category)
                ? SolarFloodLightSeoCatalog::categoryTitles()[SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG]
                : $categoryTitle);

        return $page > 1 ? $title.' - Page '.$page : $title;
    }

    public static function categoryDescription(Category $category): string
    {
        return self::cleanDescription(
            self::columnValue($category, 'meta_description')
                ?: self::columnValue($category, 'intro')
                ?: ProductContent::excerpt((string) self::columnValue($category, 'description'), 155)
                ?: 'Shop '.$category->name.' in Kenya with current prices, stock availability and delivery options.'
        );
    }

    public static function productTitle(Product $product): string
    {
        if ($customTitle = self::columnValue($product, 'seo_title')) {
            return $customTitle;
        }

        $model = ProductSeo::model($product);
        $typeLabel = ProductSeo::typeLabel($product);

        return Str::limit($model.' Price in Kenya | '.$typeLabel, 78, '');
    }

    public static function productDescription(Product $product): string
    {
        return self::cleanDescription(
            self::columnValue($product, 'meta_description')
                ?: ProductContent::excerpt($product->description, 155)
                ?: 'Buy '.ProductSeo::displayName($product).' in Kenya. View current price, wattage, specifications, availability and delivery options.'
        );
    }

    public static function pageTitle(Page $page): string
    {
        return self::columnValue($page, 'seo_title')
            ?: self::columnValue($page, 'meta_title')
            ?: $page->title;
    }

    public static function pageDescription(Page $page, ?string $fallback = null): string
    {
        return self::cleanDescription(
            self::columnValue($page, 'meta_description')
                ?: $fallback
                ?: ProductContent::excerpt($page->body, 155)
                ?: $page->title
        );
    }

    public static function canonicalOverride(object $model): ?string
    {
        return self::columnValue($model, 'canonical_url');
    }

    public static function robots(object $model): ?string
    {
        return self::columnValue($model, 'robots');
    }

    public static function primaryKeyword(object $model): ?string
    {
        return self::columnValue($model, 'primary_keyword');
    }

    public static function schemaType(object $model): ?string
    {
        return self::columnValue($model, 'schema_type');
    }

    public static function sitemapEnabled(object $model): bool
    {
        if (! method_exists($model, 'getTable') || ! self::columnReady($model->getTable(), 'sitemap_enabled')) {
            return true;
        }

        return $model->sitemap_enabled !== false && (string) $model->sitemap_enabled !== '0';
    }

    public static function openGraphTitle(object $model, string $fallback): string
    {
        return self::columnValue($model, 'og_title') ?: $fallback;
    }

    public static function openGraphDescription(object $model, string $fallback): string
    {
        return self::cleanDescription(self::columnValue($model, 'og_description') ?: $fallback);
    }

    public static function openGraphImage(object $model, ?string $fallback = null): ?string
    {
        return self::columnValue($model, 'og_image') ?: $fallback;
    }

    public static function columnReady(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table.'.'.$column;

        return $cache[$key] ??= Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    private static function columnValue(object $model, string $column): ?string
    {
        if (! method_exists($model, 'getTable') || ! self::columnReady($model->getTable(), $column)) {
            return null;
        }

        $value = trim((string) ($model->{$column} ?? ''));

        return $value !== '' ? $value : null;
    }

    private static function cleanDescription(string $description): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($description)) ?? ''), 160, '');
    }
}
