<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Support\CanonicalUrl;
use App\Support\SeoIndexing;
use App\Support\SolarFloodLightSeoCatalog;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            [
                'loc' => CanonicalUrl::route('home'),
                'lastmod' => now()->toDateString(),
                'priority' => '1.0',
            ],
        ]);

        $categoryUrls = Category::query()
            ->whereNotIn('slug', array_keys(SolarFloodLightSeoCatalog::legacyCategoryRedirects()))
            ->orderBy('updated_at', 'desc')
            ->get()
            ->filter(fn (Category $category): bool => SeoIndexing::sitemapEligible($category))
            ->map(fn (Category $category): array => [
                'loc' => CanonicalUrl::route('category.show', $category),
                'lastmod' => optional($category->updated_at)->toDateString(),
                'priority' => '0.8',
            ]);

        $productUrls = Product::query()
            ->active()
            ->orderBy('updated_at', 'desc')
            ->get()
            ->filter(fn (Product $product): bool => SeoIndexing::sitemapEligible($product))
            ->map(fn (Product $product): array => [
                'loc' => CanonicalUrl::route('product.show', $product),
                'lastmod' => optional($product->updated_at)->toDateString(),
                'priority' => '0.7',
            ]);

        $pageUrls = Page::query()
            ->orderBy('updated_at', 'desc')
            ->get()
            ->filter(fn (Page $page): bool => SeoIndexing::sitemapEligible($page))
            ->map(fn (Page $page): array => [
                'loc' => CanonicalUrl::route('pages.show', $page),
                'lastmod' => optional($page->updated_at)->toDateString(),
                'priority' => '0.6',
            ]);

        $comparisonUrls = collect(SolarFloodLightSeoCatalog::resolvableComparisonSlugs())
            ->map(fn (string $slug): array => [
                'loc' => CanonicalUrl::route('comparison.show', $slug),
                'lastmod' => null,
                'priority' => '0.6',
            ]);

        foreach (['about-us', 'contact-us', 'delivery-policy', 'returns-policy', 'warranty-policy', 'privacy-policy', 'terms-and-conditions'] as $slug) {
            if (! $pageUrls->contains('loc', CanonicalUrl::route('pages.show', ['page' => $slug]))) {
                $pageUrls->push([
                    'loc' => CanonicalUrl::route('pages.show', ['page' => $slug]),
                    'lastmod' => null,
                    'priority' => '0.4',
                ]);
            }
        }

        $urls = $urls->merge($categoryUrls)->merge($productUrls)->merge($pageUrls)->merge($comparisonUrls);

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
