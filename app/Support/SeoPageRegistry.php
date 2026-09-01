<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SeoPageRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return self::withRegistryIds(array_merge(
            self::homeDefinition(),
            self::categoryDefinitions(),
            self::contentPageDefinitions(),
            self::plannedWattageDefinitions(),
            self::plannedSecurityDefinitions(),
            self::plannedOutdoorDefinitions(),
            self::plannedApplicationDefinitions(),
            self::plannedLocationDefinitions(),
            self::plannedGuideDefinitions(),
            self::plannedBrandDefinitions()
        ));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function contentDefinitions(): array
    {
        return array_filter(
            self::definitions(),
            fn (array $definition): bool => (bool) ($definition['create_content'] ?? false)
        );
    }

    public static function definitionForCategorySlug(string $slug): ?array
    {
        $slug = Str::slug($slug);

        return Arr::first(
            self::definitions(),
            fn (array $definition): bool => ($definition['content_type'] ?? null) === 'category'
                && ($definition['category_slug'] ?? null) === $slug
        );
    }

    public static function definitionForPageSlug(string $slug): ?array
    {
        $slug = Str::slug($slug);

        return Arr::first(
            self::definitions(),
            fn (array $definition): bool => ($definition['content_type'] ?? null) === 'page'
                && ($definition['slug'] ?? null) === $slug
        );
    }

    public static function definitionForPath(string $path): ?array
    {
        $path = self::normalizePath($path);

        return self::definitions()[$path] ?? null;
    }

    public static function categorySlugForPath(string $path): ?string
    {
        $definition = self::definitionForPath($path);

        return ($definition['content_type'] ?? null) === 'category'
            ? ($definition['category_slug'] ?? null)
            : null;
    }

    public static function pathForCategorySlug(string $slug): ?string
    {
        return self::definitionForCategorySlug($slug)['path'] ?? null;
    }

    public static function pathForPage(Page|string $page): ?string
    {
        $slug = $page instanceof Page ? $page->slug : $page;
        $definition = self::definitionForPageSlug((string) $slug);

        if ($definition) {
            return $definition['path'];
        }

        if ($page instanceof Page && $page->type === 'post') {
            return '/blog/'.$page->slug;
        }

        return '/'.Str::slug((string) $slug);
    }

    /**
     * @return array<int, string>
     */
    public static function cleanCategoryPaths(): array
    {
        return array_values(array_filter(array_map(
            fn (array $definition): ?string => ($definition['content_type'] ?? null) === 'category'
                ? $definition['path']
                : null,
            self::definitions()
        )));
    }

    /**
     * @return array<int, string>
     */
    public static function categorySlugs(): array
    {
        return array_values(array_filter(array_map(
            fn (array $definition): ?string => ($definition['content_type'] ?? null) === 'category'
                ? ($definition['category_slug'] ?? null)
                : null,
            self::definitions()
        )));
    }

    /**
     * @return array<string, string>
     */
    public static function legacyCategoryRedirects(): array
    {
        return [
            'solar-flood-lights-price-in-kenya' => 'solar-flood-lights',
            'solar-floodlights' => 'solar-flood-lights',
            'solar-flood-light-price-in-kenya' => 'solar-flood-lights',
            'solar-flood-lights-for-sale-in-kenya' => 'solar-flood-lights',
            'led-solar-flood-lights' => 'solar-flood-lights',
            'outdoor-solar-flood-lights' => 'solar-outdoor-lights',
            'motion-sensor-solar-lights' => 'solar-motion-sensor-lights',
            'motion-sensor-flood-lights' => 'solar-motion-sensor-lights',
            'outdoor-security-lights' => 'solar-security-lights',
            'solar-security-light' => 'solar-security-lights',
            'solar-streetlights' => 'solar-street-lights',
            'solar-garden-wall-lights' => 'solar-garden-lights',
            'solar-light-accessories' => 'solar-light-installation-kenya',
        ];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public static function relatedLinksForCategory(Category $category): array
    {
        $slug = Str::slug($category->slug);
        $links = [];

        foreach (self::definitions() as $definition) {
            if (($definition['status'] ?? 'planned') === 'planned') {
                continue;
            }

            if (($definition['path'] ?? '') === self::pathForCategorySlug($slug)) {
                continue;
            }

            $include = false;
            if ($slug === 'solar-flood-lights') {
                $include = in_array($definition['page_type'] ?? '', ['wattage_category', 'commercial_landing', 'service_page', 'blog_post'], true);
            } elseif ($slug === 'solar-street-lights') {
                $include = Str::contains((string) ($definition['primary_keyword'] ?? ''), 'street light');
            } elseif ($slug === 'solar-security-lights') {
                $include = Str::contains((string) ($definition['primary_keyword'] ?? ''), ['security', 'motion sensor', 'PIR', 'CCTV']);
            } elseif ($slug === 'solar-motion-sensor-lights') {
                $include = Str::contains((string) ($definition['primary_keyword'] ?? ''), ['motion', 'PIR', 'security']);
            } elseif ($slug === 'solar-outdoor-lights') {
                $include = in_array($definition['page_type'] ?? '', ['category', 'application_landing', 'service_page'], true);
            }

            if ($include) {
                $links[] = [
                    'label' => (string) ($definition['menu_label'] ?? $definition['h1']),
                    'url' => SeoUrl::path($definition['path']),
                ];
            }

            if (count($links) >= 8) {
                break;
            }
        }

        return $links;
    }

    /**
     * @return array<string, array<int, array{label: string, path: string}>>
     */
    public static function navigationGroups(): array
    {
        return [
            'Solar Flood Lights' => [
                ['label' => 'Solar Flood Lights', 'path' => '/solar-flood-lights'],
                ['label' => 'Buy Solar Flood Lights', 'path' => '/buy-solar-flood-lights-kenya'],
                ['label' => 'Solar Flood Lights Nairobi', 'path' => '/solar-flood-lights-nairobi'],
                ['label' => 'Wholesale Solar Lights', 'path' => '/solar-lights-wholesale-kenya'],
            ],
            'Street Lights' => [
                ['label' => 'Solar Street Lights', 'path' => '/solar-street-lights'],
                ['label' => 'Installation', 'path' => '/solar-street-light-installation-kenya'],
                ['label' => 'All-in-One Street Lights', 'path' => '/all-in-one-solar-street-lights'],
            ],
            'Security Lights' => [
                ['label' => 'Solar Security Lights', 'path' => '/solar-security-lights'],
                ['label' => 'Motion Sensor Lights', 'path' => '/solar-motion-sensor-lights'],
                ['label' => 'PIR Solar Lights', 'path' => '/pir-solar-lights'],
                ['label' => 'Solar CCTV Lights', 'path' => '/solar-cctv-lights'],
            ],
            'Outdoor Lights' => [
                ['label' => 'Outdoor Solar Lights', 'path' => '/solar-outdoor-lights'],
                ['label' => 'Solar Wall Lights', 'path' => '/solar-wall-lights'],
                ['label' => 'Solar Garden Lights', 'path' => '/solar-garden-lights'],
                ['label' => 'Solar Gate Lights', 'path' => '/solar-gate-lights'],
            ],
            'Guides' => [
                ['label' => 'Price Guide', 'path' => '/blog/solar-flood-lights-prices-kenya'],
                ['label' => 'Buying Guide', 'path' => '/blog/best-solar-flood-lights-kenya'],
                ['label' => 'Wattage Guide', 'path' => '/blog/solar-flood-light-wattage-guide'],
                ['label' => 'Installation Guide', 'path' => '/blog/solar-flood-light-installation-guide'],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array{label: string, path: string}>>
     */
    public static function footerGroups(): array
    {
        return [
            'Shop' => [
                ['label' => 'Solar Flood Lights', 'path' => '/solar-flood-lights'],
                ['label' => 'Solar Street Lights', 'path' => '/solar-street-lights'],
                ['label' => 'Security Lights', 'path' => '/solar-security-lights'],
                ['label' => 'Outdoor Solar Lights', 'path' => '/solar-outdoor-lights'],
            ],
            'Solutions' => [
                ['label' => 'Homes', 'path' => '/solar-lights-for-homes'],
                ['label' => 'Compounds', 'path' => '/solar-lights-for-compounds'],
                ['label' => 'Parking Lots', 'path' => '/solar-lights-for-parking-lots'],
                ['label' => 'Farms', 'path' => '/solar-lights-for-farms'],
                ['label' => 'Estates', 'path' => '/solar-lights-for-estates'],
            ],
            'Help' => [
                ['label' => 'Delivery', 'path' => '/delivery-policy'],
                ['label' => 'Installation', 'path' => '/solar-light-installation-kenya'],
                ['label' => 'Contact', 'path' => '/contact-us'],
                ['label' => 'Returns', 'path' => '/returns-policy'],
                ['label' => 'Warranty', 'path' => '/warranty-policy'],
            ],
            'Company' => [
                ['label' => 'About', 'path' => '/about-us'],
                ['label' => 'Contact', 'path' => '/contact-us'],
                ['label' => 'Privacy Policy', 'path' => '/privacy-policy'],
                ['label' => 'Terms', 'path' => '/terms-and-conditions'],
            ],
        ];
    }

    public static function requiresProducts(array $definition): bool
    {
        return (bool) ($definition['requires_products'] ?? false);
    }

    public static function hasProducts(array $definition): bool
    {
        return self::productQueryForDefinition($definition)->exists();
    }

    public static function productQueryForDefinition(array $definition): Builder
    {
        $query = Product::query()->active();
        $categorySlug = $definition['category_slug'] ?? null;

        if ($categorySlug && Category::query()->where('slug', $categorySlug)->exists()) {
            $category = Category::query()->where('slug', $categorySlug)->with('children')->first();
            if ($category) {
                $ids = $category->children->pluck('id')->push($category->id)->all();
                $query->whereIn('category_id', $ids);
            }
        }

        $terms = array_values(array_filter($definition['product_terms'] ?? []));
        foreach ($terms as $term) {
            $query->where(function (Builder $termQuery) use ($term): void {
                $termQuery->where('name', 'like', '%'.$term.'%')
                    ->orWhere('slug', 'like', '%'.Str::slug($term).'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhereHas('category', function (Builder $categoryQuery) use ($term): void {
                        $categoryQuery->where('name', 'like', '%'.$term.'%')
                            ->orWhere('slug', 'like', '%'.Str::slug($term).'%');
                    });
            });
        }

        return $query;
    }

    public static function normalizePath(string $path): string
    {
        $path = '/'.trim($path, '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * @param  array<string, array<string, mixed>>  $definitions
     * @return array<string, array<string, mixed>>
     */
    private static function withRegistryIds(array $definitions): array
    {
        foreach ($definitions as $path => $definition) {
            $path = self::normalizePath($path);
            $definitions[$path]['path'] = $path;
            $definitions[$path]['registry_id'] = $definition['registry_id'] ?? trim(str_replace('/', '.', $path), '.') ?: 'home';
            $definitions[$path]['slug'] = $definition['slug'] ?? Str::slug(basename($path) ?: 'home');
            $definitions[$path]['canonical_path'] = $definition['canonical_path'] ?? $path;
            $definitions[$path]['status'] = $definition['status'] ?? 'planned';
            $definitions[$path]['indexable'] = (bool) ($definition['indexable'] ?? false);
            $definitions[$path]['sitemap_enabled'] = (bool) ($definition['sitemap_enabled'] ?? $definitions[$path]['indexable']);
            $definitions[$path]['follow'] = (bool) ($definition['follow'] ?? true);
        }

        return $definitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function homeDefinition(): array
    {
        return [
            '/' => [
                'page_type' => 'homepage',
                'primary_keyword' => 'Solar Flood Lights Kenya',
                'seo_title' => 'Solar Flood Lights Kenya | Best Prices & Fast Delivery',
                'h1' => 'Solar Flood Lights in Kenya',
                'canonical_path' => '/',
                'indexable' => true,
                'sitemap_enabled' => true,
                'status' => 'published',
                'schema_type' => 'WebSite',
                'menu_label' => 'Home',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function categoryDefinitions(): array
    {
        $categories = [
            '/solar-flood-lights' => [
                'page_type' => 'main_category',
                'content_type' => 'category',
                'category_slug' => 'solar-flood-lights',
                'primary_keyword' => 'solar flood lights price in Kenya',
                'seo_title' => 'Solar Flood Lights Price in Kenya | Buy Online',
                'h1' => 'Solar Flood Lights Price in Kenya',
                'meta_description' => 'Shop solar flood lights in Kenya for compounds, farms, security and commercial use. Compare wattages, prices and availability.',
                'intro' => 'Compare solar flood lights in Kenya for homes, compounds, farms, shops, institutions and commercial security areas. Use this category to review available wattages, current catalogue prices, stock status and practical installation notes before choosing a light.',
                'seo_content' => self::solarFloodLightsContent(),
                'faq_items' => self::floodLightFaqs(),
                'schema_type' => 'CollectionPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'product_terms' => ['solar', 'flood'],
                'menu_label' => 'Solar Flood Lights',
                'create_content' => true,
            ],
            '/solar-street-lights' => [
                'page_type' => 'category',
                'content_type' => 'category',
                'category_slug' => 'solar-street-lights',
                'primary_keyword' => 'solar street lights price in Kenya',
                'seo_title' => 'Solar Street Lights Price in Kenya | Buy Online',
                'h1' => 'Solar Street Lights in Kenya',
                'meta_description' => 'Compare solar street lights in Kenya for estates, roads, schools, churches, parking areas and public outdoor spaces.',
                'intro' => 'Solar street lights are suited to roads, estates, driveways, schools, churches, factories and parking yards where pole-mounted lighting is needed without depending fully on grid power.',
                'seo_content' => '<h2>Solar street lights for Kenyan roads and compounds</h2><p>Use solar street lights when the area needs wider road-style coverage, pole mounting and predictable dusk-to-dawn operation. Compare all-in-one and split designs, pole height, panel exposure, battery capacity and maintenance access before purchase.</p><h3>Common applications</h3><ul><li>Estate roads and access lanes.</li><li>School, church and public-space lighting.</li><li>Parking yards, driveways and perimeter roads.</li></ul>',
                'faq_items' => [
                    ['question' => 'Are solar street lights different from solar flood lights?', 'answer' => 'Yes. Street lights are usually designed for pole mounting and wider road coverage, while flood lights are often selected for walls, compounds and security zones.'],
                    ['question' => 'Do solar street lights need poles?', 'answer' => 'Many street light installations need poles or mounting arms. Confirm pole height, spacing and fixture compatibility before ordering.'],
                ],
                'schema_type' => 'CollectionPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'product_terms' => ['solar', 'street'],
                'menu_label' => 'Solar Street Lights',
                'create_content' => true,
            ],
            '/solar-security-lights' => [
                'page_type' => 'category',
                'content_type' => 'category',
                'category_slug' => 'solar-security-lights',
                'primary_keyword' => 'solar security lights Kenya',
                'seo_title' => 'Solar Security Lights Kenya | Prices & Delivery',
                'h1' => 'Solar Security Lights in Kenya',
                'meta_description' => 'Shop solar security lights in Kenya for gates, perimeter walls, CCTV zones, loading areas and commercial compounds.',
                'intro' => 'Solar security lights help illuminate entrances, perimeter walls, dark corners, loading areas and CCTV zones. Compare brightness, sensor modes, battery capacity and installation position before buying.',
                'seo_content' => '<h2>Solar security lights for homes and businesses</h2><p>Security lighting should be selected around coverage, mounting height, activation mode and expected runtime. Motion sensor models can conserve battery power, while dusk-to-dawn models are useful where continuous visibility is required.</p><h3>Where security lights fit</h3><ul><li>Gate entrances, walls and walkways.</li><li>Parking areas, shops and loading zones.</li><li>CCTV blind spots and perimeter sections.</li></ul>',
                'faq_items' => [
                    ['question' => 'Should I choose motion sensor or dusk-to-dawn security lights?', 'answer' => 'Use motion sensor lights where activation should happen only when movement is detected. Use dusk-to-dawn lighting where continuous night visibility is important.'],
                    ['question' => 'Can solar security lights support CCTV areas?', 'answer' => 'They can improve visibility around CCTV zones, but camera performance also depends on camera quality, mounting and the amount of usable light.'],
                ],
                'schema_type' => 'CollectionPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'product_terms' => ['solar', 'security'],
                'menu_label' => 'Solar Security Lights',
                'create_content' => true,
            ],
            '/solar-motion-sensor-lights' => [
                'page_type' => 'category',
                'content_type' => 'category',
                'category_slug' => 'solar-motion-sensor-lights',
                'primary_keyword' => 'solar motion sensor lights Kenya',
                'seo_title' => 'Solar Motion Sensor Lights Kenya | Best Prices',
                'h1' => 'Solar Motion Sensor Lights in Kenya',
                'meta_description' => 'Compare solar motion sensor lights in Kenya for gates, paths, walls, entrances and energy-saving outdoor security.',
                'intro' => 'Solar motion sensor lights switch to brighter output when movement is detected. They are useful for gates, paths, stair areas, entrances and security points where full brightness is not needed all night.',
                'seo_content' => '<h2>Motion sensor solar lights for efficient security lighting</h2><p>Motion sensor solar lights are practical where battery runtime matters. Check sensor range, mounting angle, brightness modes, battery size and weather rating before buying for outdoor use.</p><h3>Best places to use motion sensor lights</h3><ul><li>Gate entrances and doors.</li><li>Walkways, stairs and corridors.</li><li>Perimeter corners that need automatic lighting.</li></ul>',
                'faq_items' => [
                    ['question' => 'Do solar motion sensor lights stay on all night?', 'answer' => 'Many models use a dim mode and brighten when movement is detected. Confirm the lighting modes on the actual product page.'],
                    ['question' => 'Where should the sensor face?', 'answer' => 'Mount the light where the sensor faces the expected movement path and where the solar panel receives enough direct sunlight.'],
                ],
                'schema_type' => 'CollectionPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'product_terms' => ['solar', 'motion'],
                'menu_label' => 'Motion Sensor Lights',
                'create_content' => true,
            ],
            '/solar-outdoor-lights' => [
                'page_type' => 'category',
                'content_type' => 'category',
                'category_slug' => 'solar-outdoor-lights',
                'primary_keyword' => 'outdoor solar lights Kenya',
                'seo_title' => 'Outdoor Solar Lights Kenya | Security, Garden & Street',
                'h1' => 'Outdoor Solar Lights in Kenya',
                'meta_description' => 'Browse outdoor solar lights in Kenya for security, gardens, gates, walls, compounds, parking areas and street lighting.',
                'intro' => 'Outdoor solar lights include flood lights, street lights, wall lights, garden lights, gate lights and sensor lights. Choose based on location, brightness, mounting style and expected runtime.',
                'seo_content' => '<h2>Outdoor solar lights for Kenyan homes and businesses</h2><p>Outdoor solar lighting works best when the fixture type matches the space. Flood lights suit yards and security zones, street lights suit roads and large compounds, while garden and wall lights suit lower-brightness residential areas.</p>',
                'faq_items' => [
                    ['question' => 'Which outdoor solar light should I buy?', 'answer' => 'Start with the space: flood lights for wide security coverage, street lights for roads and poles, wall lights for entrances and garden lights for softer path lighting.'],
                    ['question' => 'Are outdoor solar lights waterproof?', 'answer' => 'Check the listed weather rating on the actual product. Outdoor suitability depends on build quality, mounting and exposure.'],
                ],
                'schema_type' => 'CollectionPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'product_terms' => ['solar', 'light'],
                'menu_label' => 'Outdoor Solar Lights',
                'create_content' => true,
            ],
        ];

        foreach (['100w', '200w', '300w'] as $wattage) {
            $label = Str::upper($wattage);
            $categories['/solar-flood-lights/'.$wattage] = [
                'page_type' => 'wattage_category',
                'content_type' => 'category',
                'category_slug' => 'solar-flood-lights-'.$wattage,
                'parent_page' => 'solar-flood-lights',
                'parent_category_slug' => 'solar-flood-lights',
                'primary_keyword' => $label.' solar flood light price in Kenya',
                'seo_title' => $label.' Solar Flood Light Price in Kenya',
                'h1' => $label.' Solar Flood Lights in Kenya',
                'meta_description' => 'Compare '.$label.' solar flood lights in Kenya for compounds, parking areas, gates and security lighting projects.',
                'intro' => 'Compare '.$label.' solar flood lights in Kenya for outdoor spaces where the mounting height, brightness and battery runtime match this wattage range.',
                'seo_content' => '<h2>'.$label.' solar flood light buying notes</h2><p>Use this wattage page only for products that genuinely match '.$label.' solar flood light intent. Confirm included panel, battery, remote, mounting accessories and weather rating on the product page before purchase.</p>',
                'faq_items' => [
                    ['question' => 'Is a '.$label.' solar flood light enough for a compound?', 'answer' => 'It depends on compound size, mounting height and brightness needed. Compare the visible specifications on each product page before ordering.'],
                    ['question' => 'Do '.$label.' solar flood lights include a battery?', 'answer' => 'Most complete solar flood lights include a battery, but the actual package should be confirmed on the product page or quotation.'],
                ],
                'schema_type' => 'CollectionPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'create_when_empty' => false,
                'product_terms' => ['solar', 'flood', $label],
                'menu_label' => $label.' Solar Flood Lights',
                'create_content' => true,
            ];
        }

        return $categories;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function contentPageDefinitions(): array
    {
        return [
            '/buy-solar-flood-lights-kenya' => [
                'page_type' => 'commercial_landing',
                'content_type' => 'page',
                'primary_keyword' => 'buy solar flood lights Kenya',
                'seo_title' => 'Buy Solar Flood Lights in Kenya | Best Prices',
                'h1' => 'Buy Solar Flood Lights in Kenya',
                'meta_description' => 'Buy solar flood lights in Kenya for homes, compounds, farms and businesses. Compare wattages, availability and ordering options.',
                'heading_two' => 'Solar Flood Light Buying Options',
                'body' => self::buySolarFloodLightsBody(),
                'schema_type' => 'WebPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'product_terms' => ['solar', 'flood'],
                'menu_label' => 'Buy Solar Flood Lights',
                'create_content' => true,
            ],
            '/solar-flood-lights-nairobi' => [
                'page_type' => 'location_landing',
                'content_type' => 'page',
                'primary_keyword' => 'solar flood lights Nairobi',
                'seo_title' => 'Solar Flood Lights Nairobi | Prices & Fast Delivery',
                'h1' => 'Solar Flood Lights in Nairobi',
                'meta_description' => 'Compare solar flood lights for Nairobi homes, compounds and businesses. Delivery and installation details should be confirmed before ordering.',
                'heading_two' => 'Nairobi Solar Lighting Enquiries',
                'body' => self::nairobiBody(),
                'schema_type' => 'WebPage',
                'status' => 'needs-content',
                'indexable' => false,
                'sitemap_enabled' => false,
                'notes' => 'Keep noindex until verified Nairobi delivery, installation and contact information is added.',
                'menu_label' => 'Solar Flood Lights Nairobi',
                'create_content' => true,
            ],
            '/solar-flood-light-supplier-kenya' => [
                'page_type' => 'b2b_landing',
                'content_type' => 'page',
                'primary_keyword' => 'solar flood light supplier Kenya',
                'seo_title' => 'Solar Flood Light Supplier in Kenya | Wholesale & Retail',
                'h1' => 'Solar Flood Light Supplier in Kenya',
                'meta_description' => 'Source solar flood lights in Kenya for retail, commercial and installation projects. Compare catalogue stock before requesting a quote.',
                'heading_two' => 'Wholesale and Retail Supply',
                'body' => self::supplierBody(),
                'schema_type' => 'WebPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'product_terms' => ['solar', 'flood'],
                'menu_label' => 'Supplier Kenya',
                'create_content' => true,
            ],
            '/solar-lights-wholesale-kenya' => [
                'page_type' => 'wholesale_landing',
                'content_type' => 'page',
                'primary_keyword' => 'wholesale solar lights Kenya',
                'seo_title' => 'Wholesale Solar Lights Kenya | Dealer & Reseller Prices',
                'h1' => 'Wholesale Solar Lights in Kenya',
                'meta_description' => 'Wholesale solar lights in Kenya for resellers, installers and bulk buyers. Compare product types and request current stock details.',
                'heading_two' => 'Dealer and Reseller Solar Lighting',
                'body' => self::wholesaleBody(),
                'schema_type' => 'WebPage',
                'status' => 'published',
                'indexable' => true,
                'requires_products' => true,
                'product_terms' => ['solar', 'light'],
                'menu_label' => 'Wholesale Solar Lights',
                'create_content' => true,
            ],
            '/solar-light-installation-kenya' => [
                'page_type' => 'service_page',
                'content_type' => 'page',
                'primary_keyword' => 'solar light installation Kenya',
                'seo_title' => 'Solar Light Installation Kenya | Professional Installers',
                'h1' => 'Solar Light Installation in Kenya',
                'meta_description' => 'Plan solar light installation in Kenya for compounds, farms, schools and businesses. Confirm site details before quotation.',
                'heading_two' => 'Outdoor Solar Lighting Installation',
                'body' => self::installationBody(),
                'schema_type' => 'Service',
                'status' => 'needs-content',
                'indexable' => false,
                'sitemap_enabled' => false,
                'notes' => 'Keep noindex until installer coverage, warranty and contact details are verified.',
                'menu_label' => 'Installation',
                'create_content' => true,
            ],
            '/blog/solar-flood-lights-prices-kenya' => [
                'page_type' => 'blog_post',
                'content_type' => 'page',
                'slug' => 'solar-flood-lights-prices-kenya',
                'primary_keyword' => 'solar flood lights prices guide Kenya',
                'seo_title' => 'Solar Flood Lights Prices in Kenya: Complete Guide',
                'h1' => 'Solar Flood Lights Prices in Kenya',
                'meta_description' => 'Learn what affects solar flood light prices in Kenya, including wattage, battery size, panels, sensors and installation needs.',
                'heading_two' => 'Price Factors and Buying Checklist',
                'body' => self::priceGuideBody(),
                'schema_type' => 'BlogPosting',
                'status' => 'published',
                'indexable' => true,
                'sitemap_enabled' => true,
                'menu_label' => 'Solar Flood Lights Price Guide',
                'create_content' => true,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function plannedSecurityDefinitions(): array
    {
        return self::plannedFromRows([
            ['/pir-solar-lights', 'category', 'PIR solar lights Kenya', 'PIR Solar Lights Kenya | Motion Sensor Security Lights', 'PIR Solar Lights in Kenya'],
            ['/solar-cctv-lights', 'category', 'solar CCTV light price in Kenya', 'Solar CCTV Lights Price in Kenya | Camera Security Lights', 'Solar CCTV Lights in Kenya'],
            ['/solar-camera-lights', 'category', 'solar camera light Kenya', 'Solar Camera Lights Kenya | Outdoor Security Lighting', 'Solar Camera Lights in Kenya'],
            ['/three-head-solar-lights', 'category', 'three head solar light Kenya', '3 Head Solar Motion Sensor Lights Kenya | Prices', 'Three-Head Solar Lights in Kenya'],
            ['/dusk-to-dawn-solar-lights', 'category', 'dusk to dawn solar lights Kenya', 'Dusk to Dawn Solar Lights Kenya | Automatic Lighting', 'Dusk-to-Dawn Solar Lights in Kenya'],
            ['/solar-perimeter-security-lights', 'category', 'solar perimeter lights Kenya', 'Solar Perimeter Security Lights Kenya | Outdoor Lighting', 'Solar Perimeter Security Lights'],
            ['/solar-security-light-installation', 'service_page', 'solar security light installation Kenya', 'Solar Security Light Installation Kenya', 'Solar Security Lighting Installation'],
            ['/solar-street-lights-with-poles', 'category', 'solar street lights with poles Kenya', 'Solar Street Lights With Poles Price in Kenya', 'Solar Street Lights With Poles in Kenya'],
            ['/all-in-one-solar-street-lights', 'category', 'all in one solar street light Kenya', 'All-in-One Solar Street Lights Kenya | Prices', 'All-in-One Solar Street Lights'],
            ['/solar-street-light-installation-kenya', 'service_page', 'solar street light installation Kenya', 'Solar Street Light Installation Kenya | Supply & Install', 'Solar Street Light Installation in Kenya'],
        ], 'planned');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function plannedWattageDefinitions(): array
    {
        $definitions = [];
        $activeFloodWattages = ['100w', '200w', '300w'];

        foreach (['30w', '40w', '50w', '60w', '70w', '120w', '150w', '400w', '500w', '800w', '1000w'] as $wattage) {
            if (in_array($wattage, $activeFloodWattages, true)) {
                continue;
            }

            $label = Str::upper($wattage);
            $definitions['/solar-flood-lights/'.$wattage] = [
                'page_type' => 'wattage_category',
                'primary_keyword' => $label.' solar flood light price in Kenya',
                'seo_title' => $label.' Solar Flood Light Price in Kenya',
                'h1' => $label.' Solar Flood Lights in Kenya',
                'parent_page' => 'solar-flood-lights',
                'status' => 'planned',
                'indexable' => false,
                'sitemap_enabled' => false,
                'schema_type' => 'CollectionPage',
                'notes' => 'Create only when relevant '.$label.' solar flood light products and unique buying copy are available.',
            ];
        }

        foreach (['60w', '100w', '150w', '200w', '300w', '400w', '500w', '1000w'] as $wattage) {
            $label = Str::upper($wattage);
            $definitions['/solar-street-lights/'.$wattage] = [
                'page_type' => 'wattage_category',
                'primary_keyword' => $label.' solar street light price in Kenya',
                'seo_title' => $label.' Solar Street Light Price in Kenya',
                'h1' => $label.' Solar Street Lights in Kenya',
                'parent_page' => 'solar-street-lights',
                'status' => 'planned',
                'indexable' => false,
                'sitemap_enabled' => false,
                'schema_type' => 'CollectionPage',
                'notes' => 'Create only when relevant '.$label.' solar street light products and unique buying copy are available.',
            ];
        }

        return $definitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function plannedOutdoorDefinitions(): array
    {
        return self::plannedFromRows([
            ['/solar-wall-lights', 'category', 'solar wall lights Kenya', 'Solar Wall Lights Kenya | Outdoor Wall Light Prices', 'Solar Wall Lights in Kenya'],
            ['/solar-garden-lights', 'category', 'solar garden lights Kenya', 'Solar Garden Lights Kenya | Garden Lighting Prices', 'Solar Garden Lights in Kenya'],
            ['/solar-gate-lights', 'category', 'solar gate lights Kenya', 'Solar Gate Lights Kenya | Outdoor Gate Lighting', 'Solar Gate Lights in Kenya'],
            ['/solar-pillar-lights', 'category', 'solar pillar lights Kenya', 'Solar Pillar Lights Kenya | Gate & Fence Lighting', 'Solar Pillar Lights in Kenya'],
            ['/solar-pathway-lights', 'category', 'solar pathway lights Kenya', 'Solar Pathway Lights Kenya | Garden & Walkway Lights', 'Solar Pathway Lights in Kenya'],
            ['/solar-bollard-lights', 'category', 'solar bollard lights Kenya', 'Solar Bollard Lights Kenya | Outdoor Garden Lighting', 'Solar Bollard Lights in Kenya'],
            ['/solar-landscape-lights', 'category', 'solar landscape lights Kenya', 'Solar Landscape Lights Kenya | Outdoor Lighting', 'Solar Landscape Lights in Kenya'],
        ], 'planned');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function plannedApplicationDefinitions(): array
    {
        return self::plannedFromRows([
            ['/solar-lights-for-homes', 'application_landing', 'solar lights for homes Kenya', 'Best Solar Lights for Homes in Kenya', 'Solar Lights for Homes in Kenya'],
            ['/solar-lights-for-compounds', 'application_landing', 'solar lights for compounds Kenya', 'Best Solar Lights for Compounds in Kenya', 'Solar Lights for Compounds in Kenya'],
            ['/solar-lights-for-parking-lots', 'application_landing', 'solar parking lot lights Kenya', 'Solar Parking Lot Lights Kenya | Commercial Lighting', 'Solar Lights for Parking Lots'],
            ['/solar-lights-for-schools', 'application_landing', 'solar lights for schools Kenya', 'Solar Lights for Schools in Kenya | Supply & Installation', 'Solar Lighting for Schools in Kenya'],
            ['/solar-lights-for-farms', 'application_landing', 'solar lights for farms Kenya', 'Solar Lights for Farms in Kenya | Security Lighting', 'Solar Lights for Farms in Kenya'],
            ['/solar-lights-for-estates', 'application_landing', 'solar lights for estates Kenya', 'Solar Lights for Estates Kenya | Roads & Security', 'Solar Lighting for Estates in Kenya'],
            ['/solar-lights-for-hotels', 'application_landing', 'solar lights for hotels Kenya', 'Solar Outdoor Lights for Hotels in Kenya', 'Solar Lights for Hotels in Kenya'],
            ['/solar-lights-for-churches', 'application_landing', 'solar lights for churches Kenya', 'Solar Lights for Churches in Kenya | Outdoor Lighting', 'Solar Lighting for Churches in Kenya'],
            ['/solar-lights-for-factories', 'application_landing', 'solar lights for factories Kenya', 'Solar Lights for Factories Kenya | Industrial Lighting', 'Solar Lighting for Factories in Kenya'],
            ['/solar-lights-for-warehouses', 'application_landing', 'solar lights for warehouses Kenya', 'Solar Lights for Warehouses Kenya | Security Lighting', 'Solar Lighting for Warehouses'],
            ['/solar-road-lighting', 'application_landing', 'solar road lights Kenya', 'Solar Road Lighting Kenya | Street Light Solutions', 'Solar Road Lighting in Kenya'],
            ['/solar-lights-for-gates-and-fences', 'application_landing', 'solar lights for gates and fences Kenya', 'Solar Lights for Gates & Fences Kenya', 'Solar Lighting for Gates and Fences'],
        ], 'planned');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function plannedLocationDefinitions(): array
    {
        $definitions = [];
        foreach (['mombasa', 'kisumu', 'nakuru', 'eldoret', 'thika', 'kiambu', 'machakos', 'ruiru'] as $city) {
            $cityLabel = Str::title($city);
            $definitions['/solar-flood-lights-'.$city] = [
                'page_type' => 'location_landing',
                'primary_keyword' => 'solar flood lights '.$cityLabel,
                'seo_title' => 'Solar Flood Lights '.$cityLabel.' | Prices & Delivery',
                'h1' => 'Solar Flood Lights in '.$cityLabel,
                'status' => 'planned',
                'indexable' => false,
                'sitemap_enabled' => false,
                'schema_type' => 'WebPage',
                'notes' => 'Create only after unique delivery and service content for '.$cityLabel.' is available.',
            ];
        }

        return $definitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function plannedGuideDefinitions(): array
    {
        return self::plannedFromRows([
            ['/blog/best-solar-flood-lights-kenya', 'blog_post', 'best solar flood lights Kenya', 'Best Solar Flood Lights in Kenya: 2026 Buying Guide', 'Best Solar Flood Lights in Kenya'],
            ['/blog/how-to-choose-solar-flood-light', 'blog_post', 'how to choose solar flood light Kenya', 'How to Choose the Right Solar Flood Light in Kenya', 'How to Choose a Solar Flood Light'],
            ['/blog/solar-flood-light-wattage-guide', 'blog_post', 'solar flood light wattage guide', 'Solar Flood Light Wattage Guide: 50W-1000W Explained', 'What Watt Solar Flood Light Do You Need?'],
            ['/blog/100w-vs-200w-solar-flood-light', 'comparison_article', '100W vs 200W solar flood light', '100W vs 200W Solar Flood Light: Which Is Better?', '100W vs 200W Solar Flood Light'],
            ['/blog/200w-vs-300w-solar-flood-light', 'comparison_article', '200W vs 300W solar flood light', '200W vs 300W Solar Flood Light: Which Should You Buy?', '200W vs 300W Solar Flood Light'],
            ['/blog/300w-vs-500w-solar-flood-light', 'comparison_article', '300W vs 500W solar flood light', '300W vs 500W Solar Flood Light Comparison', '300W vs 500W Solar Flood Light'],
            ['/blog/how-solar-flood-lights-work', 'blog_post', 'how solar flood lights work', 'How Do Solar Flood Lights Work? Complete Guide', 'How Solar Flood Lights Work'],
            ['/blog/how-long-do-solar-flood-lights-last', 'blog_post', 'how long do solar flood lights last', 'How Long Do Solar Flood Lights Last?', 'How Long Do Solar Flood Lights Last?'],
            ['/blog/solar-flood-light-installation-guide', 'blog_post', 'solar flood light installation guide', 'How to Install a Solar Flood Light: Step-by-Step Guide', 'Solar Flood Light Installation Guide'],
            ['/blog/best-solar-security-lights-kenya', 'blog_post', 'best solar security lights Kenya', 'Best Solar Security Lights in Kenya', 'Best Solar Security Lights in Kenya'],
            ['/blog/best-solar-street-lights-kenya', 'blog_post', 'best solar street lights Kenya', 'Best Solar Street Lights in Kenya: Buying Guide', 'Best Solar Street Lights in Kenya'],
        ], 'planned', 'BlogPosting');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function plannedBrandDefinitions(): array
    {
        return self::plannedFromRows([
            ['/brand/alltop-solar-lights', 'brand_landing', 'ALLTOP solar lights Kenya', 'ALLTOP Solar Lights Kenya | Flood & Street Light Prices', 'ALLTOP Solar Lights in Kenya'],
            ['/brand/jsot-solar-lights', 'brand_landing', 'JSOT solar lights Kenya', 'JSOT Solar Lights Kenya | Flood & Street Lights', 'JSOT Solar Lights in Kenya'],
            ['/brand/neelux-solar-lights', 'brand_landing', 'Neelux solar flood lights Kenya', 'Neelux Solar Flood Lights Kenya | Prices', 'Neelux Solar Lights in Kenya'],
            ['/brand/vellmax-solar-lights', 'brand_landing', 'Vellmax solar flood lights Kenya', 'Vellmax Solar Flood Lights Kenya | Prices', 'Vellmax Solar Flood Lights in Kenya'],
        ], 'planned');
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}>  $rows
     * @return array<string, array<string, mixed>>
     */
    private static function plannedFromRows(array $rows, string $status = 'planned', string $schemaType = 'WebPage'): array
    {
        $definitions = [];
        foreach ($rows as [$path, $pageType, $keyword, $title, $h1]) {
            $definitions[$path] = [
                'page_type' => $pageType,
                'primary_keyword' => $keyword,
                'seo_title' => $title,
                'h1' => $h1,
                'status' => $status,
                'indexable' => false,
                'sitemap_enabled' => false,
                'schema_type' => $schemaType,
                'notes' => 'Registry entry only. Create content and product support before indexing.',
            ];
        }

        return $definitions;
    }

    private static function solarFloodLightsContent(): string
    {
        return '<h2>How to compare solar flood lights in Kenya</h2><p>Choose a solar flood light by matching the wattage, panel exposure, battery capacity and beam angle to the area you want to light. A small gate or walkway usually needs a different fixture from a farm yard, church compound, parking lot or warehouse perimeter.</p><h3>Key buying checks</h3><ul><li>Confirm the wattage and whether the listed brightness is realistic for the mounting height.</li><li>Check the battery capacity, panel size, charging time and expected lighting hours.</li><li>Review the mounting hardware, remote control, sensor modes and weather rating.</li><li>Use product pages for current KES prices, stock status and package details.</li></ul><h3>Related buying paths</h3><p>Compare <a href="/solar-motion-sensor-lights">motion sensor solar lights</a> when you need automatic activation, or review <a href="/solar-street-lights">solar street lights</a> for pole-mounted road and estate lighting.</p>';
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private static function floodLightFaqs(): array
    {
        return [
            ['question' => 'What affects solar flood light prices in Kenya?', 'answer' => 'Price is affected by wattage, battery capacity, solar panel size, build quality, sensor features, mounting accessories and warranty terms.'],
            ['question' => 'Which wattage should I choose for a compound?', 'answer' => 'The right wattage depends on area size, mounting height and brightness needs. Compare the product specifications and avoid buying by wattage label alone.'],
            ['question' => 'Can solar flood lights work for security lighting?', 'answer' => 'Yes, when the light has enough battery capacity, panel exposure and suitable brightness for the area. Motion sensor models can extend runtime in lower-traffic areas.'],
        ];
    }

    private static function buySolarFloodLightsBody(): string
    {
        return '<p>Use this page when you are ready to compare and buy solar flood lights in Kenya. The safest buying path is to start with the area you want to light, then compare wattage, battery capacity, panel position, stock status and current catalogue price.</p><h2>Before you buy</h2><ul><li>For small gates and walkways, compare lower wattage lights and motion sensor options.</li><li>For compounds, shops and parking yards, compare medium and high-output flood lights.</li><li>For roads, estates and institutions, compare <a href="/solar-street-lights">solar street lights</a> instead of wall-mounted flood lights.</li></ul><p>Do not rely on wattage alone. Check the visible product specifications, package contents, delivery notes and warranty terms before checkout or quotation approval.</p>';
    }

    private static function nairobiBody(): string
    {
        return '<p>Nairobi buyers commonly use solar flood lights for apartment compounds, townhouses, perimeter walls, parking yards, shop fronts, warehouses and estate entrances. Product choice should account for mounting height, shaded areas, battery runtime and whether motion sensing or dusk-to-dawn lighting is required.</p><h2>Nairobi delivery and installation notes</h2><p>Keep this page noindex until the business adds verified Nairobi delivery coverage, installation availability, contact details and recommended products. Once those details are available, this page can become the single Nairobi landing page and should not be duplicated by wording variants such as solar flood lights in Nairobi.</p>';
    }

    private static function supplierBody(): string
    {
        return '<p>Solar flood light supplier pages should support contractors, businesses, resellers and institutions that need consistent stock, quotation support and product documentation. Use the catalogue to compare product types before requesting bulk pricing or installation guidance.</p><h2>What supplier buyers should confirm</h2><ul><li>Available wattages and brands.</li><li>Stock levels for repeat or bulk orders.</li><li>Warranty, replacement and delivery terms.</li><li>Whether accessories such as poles, brackets and remotes are included.</li></ul>';
    }

    private static function wholesaleBody(): string
    {
        return '<p>Wholesale solar lights in Kenya should be managed from real catalogue and inventory data. This page separates reseller and dealer intent from normal retail buying, while avoiding duplicate category pages for the same broad keyword.</p><h2>Wholesale buying checks</h2><ul><li>Confirm minimum order quantities and available product types.</li><li>Check whether pricing varies by wattage, brand or package contents.</li><li>Review delivery, warranty and after-sales support before committing to a bulk order.</li></ul>';
    }

    private static function installationBody(): string
    {
        return '<p>Solar light installation requires matching the fixture, mounting height, panel exposure, battery runtime and lighting mode to the site. This service page should remain noindex until verified installer coverage, contact details, warranty terms and service areas are added.</p><h2>Installation planning checklist</h2><ul><li>Measure the area and identify mounting positions.</li><li>Check shade, roof lines and panel exposure during the day.</li><li>Confirm whether the project needs flood lights, street lights, wall lights or mixed lighting.</li><li>Document warranty and maintenance responsibilities.</li></ul>';
    }

    private static function priceGuideBody(): string
    {
        return '<p>Solar flood lights prices in Kenya vary because products differ in wattage, panel size, battery capacity, control modes, housing quality, mounting hardware and warranty terms. A 100W model for a gate or walkway is not the same buying decision as a 300W or 500W light for a parking area, farm yard or commercial perimeter.</p><h2>What changes the price?</h2><ul><li><strong>Wattage and brightness:</strong> Higher-output lights normally cost more, but the real test is whether the light suits the mounting height and coverage area.</li><li><strong>Battery and panel size:</strong> Larger batteries and panels can improve runtime, especially during cloudy weather or long-night security use.</li><li><strong>Motion sensor or dusk-to-dawn mode:</strong> Sensor features can improve efficiency, while dusk-to-dawn lighting is useful where continuous visibility is needed.</li><li><strong>Build quality and weather rating:</strong> Outdoor fixtures should be selected for rain exposure, heat, dust and mounting conditions.</li><li><strong>Installation accessories:</strong> Poles, brackets, remotes and cabling can affect the total project cost.</li></ul><h2>How to compare current prices</h2><p>Use the <a href="/solar-flood-lights">solar flood lights price category</a> for catalogue prices once products are loaded. Compare nearby wattages instead of jumping straight to the largest number on the label.</p><h2>Buying advice</h2><p>For homes and small compounds, start with the area size and whether you need motion sensing. For farms, schools, churches and businesses, plan the number of lights, mounting height, panel exposure and maintenance access before buying.</p>';
    }
}
