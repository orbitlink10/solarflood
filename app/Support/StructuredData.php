<?php

namespace App\Support;

use App\Models\HomepageContent;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Collection;

class StructuredData
{
    /**
     * @param  array<int, string>  $images
     */
    public static function product(Product $product, array $images, string $description, string $canonicalUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => ProductSeo::displayName($product),
            'image' => array_values(array_filter($images)),
            'description' => $description,
            'sku' => $product->sku,
            'mpn' => ProductSeo::model($product),
            'brand' => [
                '@type' => 'Brand',
                'name' => ProductSeo::brand($product),
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonicalUrl,
                'priceCurrency' => 'KES',
                'price' => number_format((float) $product->price, 2, '.', ''),
                'availability' => $product->stock > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => config('business.name', config('app.name', 'Mikrotik Kenya')),
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ],
                array_values($items),
                array_keys(array_values($items))
            ),
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    public static function collectionPage(string $name, string $description, string $url, Collection $products): array
    {
        $schema = self::webPage('CollectionPage', $name, $description, $url);

        if ($products->isNotEmpty()) {
            $schema['mainEntity'] = self::itemList($products, $url);
        }

        return $schema;
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    public static function itemList(Collection $products, string $pageUrl): array
    {
        return [
            '@type' => 'ItemList',
            'url' => $pageUrl,
            'numberOfItems' => $products->count(),
            'itemListElement' => $products->values()->map(fn (Product $product, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => CanonicalUrl::route('product.show', $product),
                'name' => ProductSeo::displayName($product),
            ])->all(),
        ];
    }

    public static function webPage(string $type, string $name, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => config('app.name', 'Solar Flood Lights Kenya'),
                'url' => CanonicalUrl::normalize('/'),
            ],
        ];
    }

    public static function article(Page $page, string $description, string $url): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => SeoMetadata::schemaType($page) ?: 'BlogPosting',
            'headline' => $page->title,
            'description' => $description,
            'url' => $url,
            'dateModified' => optional($page->updated_at)->toAtomString(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('business.name', config('app.name', 'Solar Flood Lights Kenya')),
            ],
        ];

        if ($page->created_at) {
            $schema['datePublished'] = $page->created_at->toAtomString();
        }

        if ($page->image_url) {
            $schema['image'] = [CanonicalUrl::absoluteAsset($page->image_url)];
        }

        return array_filter($schema, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public static function service(Page $page, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $page->title,
            'description' => $description,
            'url' => $url,
            'provider' => [
                '@type' => 'Organization',
                'name' => config('business.name', config('app.name', 'Solar Flood Lights Kenya')),
                'url' => CanonicalUrl::normalize('/'),
            ],
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Kenya',
            ],
        ];
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $items
     */
    public static function faq(array $items): ?array
    {
        $items = array_values(array_filter($items, fn (array $item): bool => $item['question'] !== '' && $item['answer'] !== ''));
        if ($items === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ], $items),
        ];
    }

    public static function organization(?HomepageContent $homepageContent = null): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('business.name', config('app.name', 'Mikrotik Kenya')),
            'url' => CanonicalUrl::normalize('/'),
        ];
        if (config('business.legal_name')) {
            $schema['legalName'] = config('business.legal_name');
        }

        if ($homepageContent?->siteLogoUrl()) {
            $schema['logo'] = CanonicalUrl::absoluteAsset($homepageContent->siteLogoUrl());
        }

        $phone = $homepageContent?->contactPhone() ?: config('business.phone');

        if ($phone) {
            $schema['telephone'] = $phone;
        }

        if (config('business.email')) {
            $schema['email'] = config('business.email');
        }

        if (config('business.address')) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => config('business.address'),
            ];
        }

        if (config('business.social_profiles')) {
            $schema['sameAs'] = config('business.social_profiles');
        }

        return $schema;
    }

    public static function website(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name', 'Mikrotik Kenya'),
            'url' => CanonicalUrl::normalize('/'),
        ];

        if (CanonicalUrl::normalize('/') !== '') {
            $schema['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => CanonicalUrl::normalize('/').'?search={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        return $schema;
    }
}
