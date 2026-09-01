<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomepageContent;
use App\Models\Page;
use App\Models\Product;
use App\Models\Testimonial;
use App\Support\CanonicalUrl;
use App\Support\ProductContent;
use App\Support\ProductSeo;
use App\Support\SeoMetadata;
use App\Support\SolarFloodLightSeoCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    private const ROUTER_PRICES_CATEGORY_NAME = 'Solar Flood Lights Price in Kenya';

    private const ROUTER_PRICES_CATEGORY_SLUG = 'solar-flood-lights-price-in-kenya';

    private const ROUTER_PRODUCTS_LIMIT = 6;

    public function index(Request $request): View|RedirectResponse
    {
        $category = $request->filled('category')
            ? Category::query()->find($request->integer('category'))
            : null;

        return $this->renderCatalog($request, $category);
    }

    public function showCategory(Request $request, string $category): View|RedirectResponse
    {
        $resolvedCategory = $this->resolveCategory($category);

        if ($resolvedCategory instanceof RedirectResponse) {
            return $resolvedCategory;
        }

        return $this->renderCatalog($request, $resolvedCategory);
    }

    public function show(string $product): View|RedirectResponse
    {
        $product = $this->resolveProduct($product);

        if ($product instanceof RedirectResponse) {
            return $product;
        }

        $product->load(['vendor', 'category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')]);

        if ($product->status !== 'active' || ! $product->vendor?->is_approved) {
            abort(404);
        }

        $relatedProducts = Product::query()
            ->with(['vendor', 'category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
            ->active()
            ->whereKeyNot($product->id)
            ->when($product->category_id, fn (Builder $query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->limit(4)
            ->get();

        return view('product.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'relatedCategories' => $this->relatedCategories($product->category),
            'comparisonLinks' => ProductSeo::comparisonLinks($product),
        ]);
    }

    public function showPage(string $page): View|RedirectResponse
    {
        $requestedSlug = Str::slug($page);
        $page = Page::query()->whereRaw('LOWER(slug) = ?', [Str::lower($requestedSlug)])->first();

        if (! $page) {
            return $this->showTrustPage($requestedSlug);
        }

        if ($page->slug !== $requestedSlug) {
            return redirect()->route('pages.show', ['page' => $page->slug], 301);
        }

        return view('page.show', [
            'page' => $page,
            'pageBody' => ProductContent::sanitizeRichText($page->body) ?: '<p>No content available.</p>',
            'pageMetaDescription' => SeoMetadata::pageDescription($page, ProductContent::excerpt($page->body, 160)),
        ]);
    }

    public function redirectLegacyPage(string $page): RedirectResponse
    {
        return redirect()->route('pages.show', ['page' => Str::slug($page)], 301);
    }

    public function redirectLegacyProduct(string $product): RedirectResponse
    {
        $product = $this->findProductBySlug($product);

        abort_unless($product, 404);

        return redirect()->route('product.show', $product, 301);
    }

    public function redirectLegacyCategory(string $category): RedirectResponse
    {
        $resolvedCategory = $this->resolveCategory($category, true);

        if ($resolvedCategory instanceof RedirectResponse) {
            return $resolvedCategory;
        }

        return redirect()->route('category.show', $resolvedCategory, 301);
    }

    public function redirectTopLevelCategory(string $categorySlug): RedirectResponse
    {
        $targetSlug = SolarFloodLightSeoCatalog::targetSlugForTopLevel($categorySlug);

        abort_unless($targetSlug, 404);

        $targetCategory = Category::query()->where('slug', $targetSlug)->first();

        if ($targetCategory) {
            return redirect()->route('category.show', $targetCategory, 301);
        }

        return redirect()->route('home', [], 301);
    }

    private function renderCatalog(Request $request, ?Category $currentCategory = null): View|RedirectResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category): bool => SolarFloodLightSeoCatalog::isSolarCategory($category))
            ->values();

        $search = trim((string) $request->query('search', ''));
        $searchSlug = Str::slug($search);
        $normalizedSearch = Str::lower($search);
        $productsQuery = Product::query()
            ->with(['vendor', 'category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
            ->active();

        $selectedCategory = null;
        if ($currentCategory) {
            $currentCategory->loadMissing('children', 'parent');
            $selectedCategory = $currentCategory->parent_id ?: $currentCategory->id;
            $productsQuery->whereIn('category_id', $this->catalogCategoryIds($currentCategory));
        }

        if ($search !== '' && ! $currentCategory) {
            $exactProduct = Product::query()
                ->active()
                ->where(function ($query) use ($normalizedSearch, $searchSlug): void {
                    $query->whereRaw('LOWER(name) = ?', [$normalizedSearch])
                        ->orWhereRaw('LOWER(sku) = ?', [$normalizedSearch]);

                    if ($searchSlug !== '') {
                        $query->orWhere('slug', $searchSlug);
                    }
                })
                ->first();

            if ($exactProduct) {
                return redirect()->route('product.show', $exactProduct);
            }
        }

        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search, $searchSlug): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('meta_description', 'like', '%'.$search.'%')
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('shop_name', 'like', '%'.$search.'%'));

                if ($searchSlug !== '') {
                    $query->orWhere('slug', 'like', '%'.$searchSlug.'%');
                }
            });
        }

        $homepageProductCategory = $search === '' && ! $currentCategory
            ? $this->routerPricesCategory()
            : null;
        $featuredProductIds = $search === '' && ! $currentCategory
            ? HomepageContent::current()->featuredProductIds()
            : [];

        if ($featuredProductIds !== []) {
            $homepageFeaturedProducts = Product::query()
                ->with(['vendor', 'category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
                ->active()
                ->whereIn('id', $featuredProductIds)
                ->get()
                ->sortBy(function (Product $product) use ($featuredProductIds): int {
                    $position = array_search($product->id, $featuredProductIds, true);

                    return $position === false ? PHP_INT_MAX : $position;
                })
                ->take(self::ROUTER_PRODUCTS_LIMIT)
                ->values();
        } elseif ($homepageProductCategory) {
            $homepageFeaturedProducts = Product::query()
                ->with(['vendor', 'category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
                ->active()
                ->whereIn('category_id', $this->catalogCategoryIds($homepageProductCategory))
                ->latest()
                ->limit(self::ROUTER_PRODUCTS_LIMIT)
                ->get();
        } else {
            $homepageFeaturedProducts = collect();
        }

        if ($homepageFeaturedProducts->isNotEmpty()) {
            $productsQuery->whereNotIn('id', $homepageFeaturedProducts->modelKeys());
        }

        $products = $productsQuery->latest()->paginate(24)->withQueryString();
        $usedCategoryFallback = false;

        if ($products->total() === 0 && $currentCategory && SolarFloodLightSeoCatalog::isBroadSolarCategory($currentCategory)) {
            $products = SolarFloodLightSeoCatalog::solarProductsQuery()
                ->latest()
                ->paginate(24)
                ->withQueryString();
            $usedCategoryFallback = $products->total() > 0;
        }

        $routerPriceTableProducts = collect();
        $isRouterAuthorityPage = $currentCategory && SolarFloodLightSeoCatalog::isPriceAuthorityCategory($currentCategory);

        if ($isRouterAuthorityPage) {
            $routerPriceTableProducts = Product::query()
                ->with(['vendor', 'category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
                ->active()
                ->whereIn('category_id', $this->catalogCategoryIds($currentCategory))
                ->orderBy('name')
                ->limit(48)
                ->get();

            if ($routerPriceTableProducts->isEmpty()) {
                $routerPriceTableProducts = SolarFloodLightSeoCatalog::solarProductsQuery()
                    ->where(function (Builder $query): void {
                        $query->where('name', 'like', '%flood%')
                            ->orWhere('name', 'like', '%light%')
                            ->orWhere('name', 'like', '%solar%')
                            ->orWhere('slug', 'like', '%flood%')
                            ->orWhere('slug', 'like', '%solar%');
                    })
                    ->orderBy('name')
                    ->limit(48)
                    ->get();
            }
        }

        return view('home', [
            'categories' => $categories,
            'featuredCategories' => $this->featuredCategories(),
            'homepageContent' => HomepageContent::current(),
            'homepageProductCategory' => $homepageProductCategory,
            'homepageFeaturedProducts' => $homepageFeaturedProducts,
            'products' => $products,
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'testimonials' => Testimonial::homepageItems(),
            'currentCategory' => $currentCategory,
            'usedCategoryFallback' => $usedCategoryFallback,
            'isRouterAuthorityPage' => $isRouterAuthorityPage,
            'routerPriceTableProducts' => $routerPriceTableProducts,
            'routerFaqItems' => SolarFloodLightSeoCatalog::priceFaqItems(),
            'relatedCategories' => $this->relatedCategories($currentCategory),
            'homepageComparisonLinks' => $search === '' && ! $currentCategory
                ? $this->homepageComparisonLinks()
                : [],
        ]);
    }

    /**
     * @return array<int, array{url: string, label: string}>
     */
    private function homepageComparisonLinks(): array
    {
        $labels = SolarFloodLightSeoCatalog::comparisonPages();
        $links = [];

        foreach (SolarFloodLightSeoCatalog::resolvableComparisonSlugs() as $slug) {
            $links[] = [
                'url' => route('comparison.show', $slug),
                'label' => $labels[$slug] ?? $slug,
            ];
        }

        return $links;
    }

    private function routerPricesCategory(): ?Category
    {
        $routerSlugs = $this->routerAuthoritySlugs();

        $candidates = Category::query()
            ->where(function (Builder $query) use ($routerSlugs): void {
                $query->whereRaw('LOWER(name) = ?', [Str::lower(self::ROUTER_PRICES_CATEGORY_NAME)])
                    ->orWhereIn('slug', $routerSlugs);
            })
            ->with('children')
            ->get()
            ->sortBy(function (Category $category) use ($routerSlugs): int {
                if ($category->slug === self::ROUTER_PRICES_CATEGORY_SLUG) {
                    return 0;
                }

                $position = array_search($category->slug, $routerSlugs, true);

                return $position === false ? 999 : $position + 1;
            })
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates->first(function (Category $category): bool {
            return Product::query()
                ->active()
                ->whereIn('category_id', $this->catalogCategoryIds($category))
                ->exists();
        }) ?: $candidates->first();
    }

    /**
     * @return array<int, string>
     */
    private function routerAuthoritySlugs(): array
    {
        return array_values(array_unique(array_merge(
            [self::ROUTER_PRICES_CATEGORY_SLUG, SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG],
            array_keys(array_filter(
                SolarFloodLightSeoCatalog::legacyCategoryRedirects(),
                fn (string $target): bool => $target === SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG
            ))
        )));
    }

    /**
     * @return array<int>
     */
    private function catalogCategoryIds(Category $category): array
    {
        if ($category->parent_id) {
            return [$category->id];
        }

        $category->loadMissing('children');

        return array_merge([$category->id], $category->children->pluck('id')->all());
    }

    private function resolveCategory(string $slug, bool $forceRedirect = false): Category|RedirectResponse
    {
        $requestedSlug = Str::slug($slug);
        $legacyTarget = SolarFloodLightSeoCatalog::targetSlugForLegacy($requestedSlug);

        if ($legacyTarget && ($forceRedirect || $legacyTarget !== $requestedSlug)) {
            $targetCategory = Category::query()->where('slug', $legacyTarget)->first();

            if ($targetCategory) {
                return redirect()->route('category.show', $targetCategory, 301);
            }
        }

        $category = Category::query()->whereRaw('LOWER(slug) = ?', [Str::lower($requestedSlug)])->first();

        if (! $category && $requestedSlug === SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG) {
            $category = $this->routerPricesCategory();
        }

        abort_unless($category, 404);

        if ($category->slug !== $requestedSlug) {
            if ($requestedSlug === SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG && SolarFloodLightSeoCatalog::isPriceAuthorityCategory($category)) {
                return $category;
            }

            return redirect()->route('category.show', $category, 301);
        }

        return $category;
    }

    private function resolveProduct(string $slug): Product|RedirectResponse
    {
        $requestedSlug = Str::slug($slug);
        $product = $this->findProductBySlug($requestedSlug);

        abort_unless($product, 404);

        if ($product->slug !== $requestedSlug) {
            return redirect()->route('product.show', $product, 301);
        }

        return $product;
    }

    private function findProductBySlug(string $slug): ?Product
    {
        return Product::query()->whereRaw('LOWER(slug) = ?', [Str::lower(Str::slug($slug))])->first();
    }

    private function showTrustPage(string $slug): View
    {
        $trustPages = [
            'about-us' => [
                'title' => 'About Us',
                'heading' => 'About Solar Flood Lights Kenya',
                'summary' => 'Information about the business behind this solar flood lights website can be added from the admin content area.',
            ],
            'contact-us' => [
                'title' => 'Contact Us',
                'heading' => 'Contact Solar Flood Lights Kenya',
                'summary' => 'Use the available contact details below to enquire about products, quotations, delivery and support.',
            ],
            'delivery-policy' => [
                'title' => 'Delivery Policy',
                'heading' => 'Delivery Policy',
                'summary' => 'Delivery availability, cost and timelines are confirmed before dispatch based on order size, stock location and destination.',
            ],
            'returns-policy' => [
                'title' => 'Returns Policy',
                'heading' => 'Returns Policy',
                'summary' => 'Return eligibility depends on product condition, seller terms and the reason for return. Confirm terms before completing high-value orders.',
            ],
            'warranty-policy' => [
                'title' => 'Warranty Policy',
                'heading' => 'Warranty Policy',
                'summary' => 'Warranty coverage depends on the product, seller and supplier terms. Confirm warranty status before purchase or quotation approval.',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'heading' => 'Privacy Policy',
                'summary' => 'Customer information is used to process accounts, orders, delivery communication and support requests.',
            ],
            'terms-and-conditions' => [
                'title' => 'Terms and Conditions',
                'heading' => 'Terms and Conditions',
                'summary' => 'Orders, quotations, payments, delivery and support are handled under the seller terms shown during purchase or direct enquiry.',
            ],
        ];

        abort_unless(isset($trustPages[$slug]), 404);

        return view('page.trust', [
            'slug' => $slug,
            'trustPage' => $trustPages[$slug],
            'canonicalUrl' => CanonicalUrl::route('pages.show', ['page' => $slug]),
        ]);
    }

    private function featuredCategories()
    {
        $primarySlugs = array_keys(SolarFloodLightSeoCatalog::primaryCategories());
        $featuredSlugs = array_values(array_unique(array_merge($primarySlugs, $this->routerAuthoritySlugs())));

        return Category::query()
            ->whereIn('slug', $featuredSlugs)
            ->get()
            ->sortBy(function (Category $category) use ($primarySlugs): int {
                $targetSlug = SolarFloodLightSeoCatalog::targetSlugForLegacy($category->slug) ?: $category->slug;
                $position = array_search($targetSlug, $primarySlugs, true);

                return $position === false ? 999 : $position;
            })
            ->unique(fn (Category $category): string => SolarFloodLightSeoCatalog::targetSlugForLegacy($category->slug) ?: $category->slug)
            ->values();
    }

    private function relatedCategories(?Category $currentCategory)
    {
        $primarySlugs = array_keys(SolarFloodLightSeoCatalog::primaryCategories());

        return Category::query()
            ->whereIn('slug', $primarySlugs)
            ->when($currentCategory, fn ($query) => $query->whereKeyNot($currentCategory->id))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }
}
