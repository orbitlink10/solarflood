<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SolarFloodLightSeoCatalog
{
    public const PRICE_AUTHORITY_SLUG = 'solar-flood-lights';

    /**
     * Backwards-compatible alias for older code/tests that still refer to the
     * previous product domain.
     */
    public const ROUTER_AUTHORITY_SLUG = self::PRICE_AUTHORITY_SLUG;

    /**
     * @return array<string, array{name: string, meta_description: string, description: string}>
     */
    public static function primaryCategories(): array
    {
        $categories = [];

        foreach (SeoPageRegistry::contentDefinitions() as $definition) {
            if (($definition['content_type'] ?? null) !== 'category') {
                continue;
            }

            if (! (bool) ($definition['create_when_empty'] ?? true) && ! SeoPageRegistry::hasProducts($definition)) {
                continue;
            }

            $categories[$definition['category_slug']] = [
                'name' => $definition['h1'],
                'meta_description' => $definition['meta_description'],
                'description' => $definition['seo_content'] ?? '<p>'.$definition['meta_description'].'</p>',
            ];
        }

        return $categories;
    }

    /**
     * @return array<string, string>
     */
    public static function categoryTitles(): array
    {
        $titles = [];

        foreach (SeoPageRegistry::definitions() as $definition) {
            if (($definition['content_type'] ?? null) === 'category') {
                $titles[$definition['category_slug']] = $definition['seo_title'];
            }
        }

        return $titles;
    }

    /**
     * @return array<string, string>
     */
    public static function comparisonPages(): array
    {
        return [
            '100w-vs-200w-solar-flood-light' => '100W vs 200W Solar Flood Light',
            '200w-vs-300w-solar-flood-light' => '200W vs 300W Solar Flood Light',
            'motion-sensor-vs-dusk-to-dawn-solar-lights' => 'Motion Sensor vs Dusk-to-Dawn Solar Lights',
            'solar-flood-light-vs-street-light' => 'Solar Flood Light vs Street Light',
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function comparisonProducts(): array
    {
        return [
            '100w-vs-200w-solar-flood-light' => ['100W', '200W'],
            '200w-vs-300w-solar-flood-light' => ['200W', '300W'],
            'motion-sensor-vs-dusk-to-dawn-solar-lights' => ['Motion Sensor', 'Dusk to Dawn'],
            'solar-flood-light-vs-street-light' => ['Solar Flood Light', 'Solar Street Light'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function resolvableComparisonSlugs(): array
    {
        $comparisons = self::comparisonProducts();
        if ($comparisons === []) {
            return [];
        }

        $products = Product::query()->active()->get(['id', 'name', 'slug', 'sku']);
        $resolvable = [];

        foreach ($comparisons as $slug => [$left, $right]) {
            if (self::findMatchingProduct($products, $left) && self::findMatchingProduct($products, $right)) {
                $resolvable[] = $slug;
            }
        }

        return $resolvable;
    }

    public static function navLabel(Category $category): string
    {
        $slug = Str::slug($category->slug);

        if ($slug === self::PRICE_AUTHORITY_SLUG || self::isPriceAuthorityCategory($category)) {
            return 'Solar Flood Lights';
        }

        if ($definition = SeoPageRegistry::definitionForCategorySlug($slug)) {
            return $definition['menu_label'] ?? $definition['h1'];
        }

        $name = trim((string) $category->name);
        $name = preg_replace('/\s*[-|:]\s*price(s)?\s*in\s*kenya\s*$/iu', '', $name) ?? $name;
        $name = preg_replace('/\s*price(s)?\s*in\s*kenya\s*$/iu', '', $name) ?? $name;
        $name = preg_replace('/\s*for\s*sale\s*in\s*kenya\s*$/iu', '', $name) ?? $name;

        return $name !== '' ? $name : $category->name;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    private static function findMatchingProduct($products, string $needle): bool
    {
        $needleSlug = str($needle)->lower()->replace('+', '-')->replace('_', '-')->slug()->toString();
        $needleLower = Str::lower($needle);

        return $products->contains(
            fn (Product $product): bool => Str::contains(Str::lower($product->name), $needleLower)
                || Str::contains(Str::lower((string) $product->sku), $needleLower)
                || Str::contains(Str::lower((string) $product->slug), $needleSlug)
        );
    }

    /**
     * @return array<string, string>
     */
    public static function legacyCategoryRedirects(): array
    {
        return SeoPageRegistry::legacyCategoryRedirects();
    }

    /**
     * @return array<string, string>
     */
    public static function topLevelCategoryRedirects(): array
    {
        return self::legacyCategoryRedirects();
    }

    public static function isPriceAuthorityCategory(?Category $category): bool
    {
        if (! $category) {
            return false;
        }

        return in_array($category->slug, array_merge(
            [self::PRICE_AUTHORITY_SLUG],
            array_keys(array_filter(
                self::legacyCategoryRedirects(),
                fn (string $target): bool => $target === self::PRICE_AUTHORITY_SLUG
            ))
        ), true);
    }

    public static function isRouterAuthorityCategory(?Category $category): bool
    {
        return self::isPriceAuthorityCategory($category);
    }

    public static function isBroadSolarCategory(?Category $category): bool
    {
        if (! $category) {
            return false;
        }

        return in_array(Str::slug($category->slug), [
            'solar',
            'solar-lights',
            'solar-lighting',
            'solar-products',
            'solar-products-in-kenya',
            'solar-flood-lights-kenya',
        ], true);
    }

    public static function isBroadMikrotikCategory(?Category $category): bool
    {
        return self::isBroadSolarCategory($category);
    }

    public static function isSolarCategory(?Category $category): bool
    {
        if (! $category) {
            return false;
        }

        $slug = Str::slug($category->slug);
        if (in_array($slug, SeoPageRegistry::categorySlugs(), true)) {
            return true;
        }

        $text = Str::lower($category->name.' '.$category->slug);

        return Str::contains($text, ['solar', 'flood light', 'street light', 'security light', 'outdoor light']);
    }

    public static function targetSlugForLegacy(string $slug): ?string
    {
        return self::legacyCategoryRedirects()[Str::slug($slug)] ?? null;
    }

    public static function targetSlugForTopLevel(string $slug): ?string
    {
        return self::topLevelCategoryRedirects()[Str::slug($slug)] ?? null;
    }

    public static function productIntentSlug(Product $product): ?string
    {
        $text = Str::lower(implode(' ', [
            $product->name,
            $product->slug,
            $product->sku,
            $product->category?->name,
            $product->category?->slug,
        ]));

        if (! Str::contains($text, ['solar', 'flood', 'light', 'street', 'security', 'motion', 'pir', 'cctv'])) {
            return null;
        }

        if (Str::contains($text, ['street light', 'streetlight', 'road light', 'estate light', 'all-in-one'])) {
            return 'solar-street-lights';
        }

        if (Str::contains($text, ['motion', 'sensor', 'pir'])) {
            return 'solar-motion-sensor-lights';
        }

        if (Str::contains($text, ['security', 'perimeter', 'cctv', 'camera', 'high mast', 'commercial'])) {
            return 'solar-security-lights';
        }

        if (Str::contains($text, ['garden', 'wall', 'gate', 'pathway', 'patio', 'bollard', 'landscape'])) {
            return 'solar-outdoor-lights';
        }

        if (Str::contains($text, ['outdoor', 'yard', 'compound', 'parking', 'farm'])) {
            return 'solar-outdoor-lights';
        }

        if (Str::contains($text, ['flood', 'floodlight', 'flood light', 'solar light', 'solar'])) {
            return self::PRICE_AUTHORITY_SLUG;
        }

        return null;
    }

    public static function solarProductsQuery(): Builder
    {
        return self::applySolarLightingScope(
            Product::query()
                ->with(['vendor', 'category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
                ->active()
        );
    }

    public static function applySolarLightingScope(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('name', 'like', '%solar%')
                ->orWhere('name', 'like', '%flood%')
                ->orWhere('name', 'like', '%street light%')
                ->orWhere('name', 'like', '%security light%')
                ->orWhere('name', 'like', '%motion sensor%')
                ->orWhere('slug', 'like', '%solar%')
                ->orWhere('slug', 'like', '%flood%')
                ->orWhere('description', 'like', '%solar%')
                ->orWhere('description', 'like', '%flood%')
                ->orWhereHas('category', function (Builder $categoryQuery): void {
                    $categoryQuery->where('name', 'like', '%solar%')
                        ->orWhere('name', 'like', '%flood%')
                        ->orWhere('name', 'like', '%street light%')
                        ->orWhere('name', 'like', '%security light%')
                        ->orWhere('slug', 'like', '%solar%')
                        ->orWhere('slug', 'like', '%flood%');
                });
        });
    }

    public static function mikrotikProductsQuery(): Builder
    {
        return self::solarProductsQuery();
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    public static function priceFaqItems(): array
    {
        return [
            [
                'question' => 'Which solar flood light wattage is best for a Kenyan home compound?',
                'answer' => 'Most home compounds use lower or medium wattage lights depending on the mounting height, area size and brightness needed. Larger yards, farms and commercial spaces may need multiple units or higher wattage.',
            ],
            [
                'question' => 'Do solar flood light prices include the panel and battery?',
                'answer' => 'Product pages should state what is included. Many solar flood lights include a panel, battery, remote and mounting hardware, but confirm the package before checkout or quotation approval.',
            ],
            [
                'question' => 'Can solar flood lights work through rainy seasons?',
                'answer' => 'A good installation should match the panel, battery capacity and lighting mode to expected runtime. Confirm charging time, battery capacity and weather rating before buying for critical security lighting.',
            ],
        ];
    }

    public static function routerFaqItems(): array
    {
        return self::priceFaqItems();
    }
}
