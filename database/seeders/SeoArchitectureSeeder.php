<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomepageContent;
use App\Models\Page;
use App\Models\SeoPage;
use App\Support\CanonicalUrl;
use App\Support\SeoDuplicateGuard;
use App\Support\SeoIndexing;
use App\Support\SeoPageRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SeoArchitectureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SeoPageRegistry::definitions() as $definition) {
            $contentModel = null;

            if (($definition['create_content'] ?? false) && ($definition['content_type'] ?? null) === 'category') {
                $contentModel = $this->syncCategory($definition);
            }

            if (($definition['create_content'] ?? false) && ($definition['content_type'] ?? null) === 'page') {
                $contentModel = $this->syncPage($definition);
            }

            $this->syncRegistryRecord($definition, $contentModel);
        }

        $this->syncHomepageContent();
    }

    private function syncCategory(array $definition): ?Category
    {
        $slug = $definition['category_slug'];

        if (! (bool) ($definition['create_when_empty'] ?? true) && ! SeoPageRegistry::hasProducts($definition)) {
            return Category::query()->where('slug', $slug)->first();
        }

        $parentId = null;
        if (! empty($definition['parent_category_slug'])) {
            $parent = Category::query()->where('slug', $definition['parent_category_slug'])->first();
            if (! $parent) {
                return null;
            }

            $parentId = $parent->id;
        }

        return Category::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $definition['h1'],
                'meta_description' => $definition['meta_description'],
                'primary_keyword' => $definition['primary_keyword'],
                'parent_id' => $parentId,
                'description' => $definition['seo_content'] ?? '<p>'.$definition['meta_description'].'</p>',
                'seo_title' => $definition['seo_title'],
                'canonical_url' => CanonicalUrl::normalize($definition['canonical_path']),
                'robots' => ($definition['status'] ?? 'planned') === 'published' && (bool) ($definition['indexable'] ?? false)
                    ? null
                    : 'noindex,follow',
                'og_title' => $definition['seo_title'],
                'og_description' => $definition['meta_description'],
                'schema_type' => $definition['schema_type'] ?? 'CollectionPage',
                'sitemap_enabled' => (bool) ($definition['sitemap_enabled'] ?? false),
                'intro' => $definition['intro'] ?? $definition['meta_description'],
                'seo_content' => $definition['seo_content'] ?? null,
                'faq_items' => $definition['faq_items'] ?? null,
            ]
        );
    }

    private function syncPage(array $definition): Page
    {
        $slug = $definition['slug'];
        $type = str_starts_with($definition['path'], '/blog/') ? 'post' : 'page';

        return Page::updateOrCreate(
            ['slug' => $slug],
            [
                'meta_title' => $definition['seo_title'],
                'meta_description' => $definition['meta_description'],
                'primary_keyword' => $definition['primary_keyword'],
                'title' => $definition['h1'],
                'heading_two' => $definition['heading_two'] ?? $definition['h1'],
                'type' => $type,
                'body' => $definition['body'] ?? '<p>'.$definition['meta_description'].'</p>',
                'seo_title' => $definition['seo_title'],
                'canonical_url' => CanonicalUrl::normalize($definition['canonical_path']),
                'robots' => ($definition['status'] ?? 'planned') === 'published' && (bool) ($definition['indexable'] ?? false)
                    ? null
                    : 'noindex,follow',
                'og_title' => $definition['seo_title'],
                'og_description' => $definition['meta_description'],
                'schema_type' => $definition['schema_type'] ?? ($type === 'post' ? 'BlogPosting' : 'WebPage'),
                'sitemap_enabled' => (bool) ($definition['sitemap_enabled'] ?? false),
            ]
        );
    }

    private function syncRegistryRecord(array $definition, Category|Page|null $contentModel): void
    {
        $indexable = SeoIndexing::definitionIndexable($definition);
        $canonicalUrl = CanonicalUrl::normalize($definition['canonical_path']);

        SeoPage::updateOrCreate(
            ['registry_id' => $definition['registry_id']],
            [
                'page_type' => $definition['page_type'],
                'slug' => $definition['slug'],
                'full_url' => CanonicalUrl::normalize($definition['path']),
                'primary_keyword' => $definition['primary_keyword'],
                'primary_keyword_normalized' => SeoDuplicateGuard::normalizeIntent($definition['primary_keyword']),
                'seo_title' => $definition['seo_title'],
                'h1' => $definition['h1'],
                'canonical_url' => $canonicalUrl,
                'indexable' => $indexable,
                'follow' => (bool) ($definition['follow'] ?? true),
                'sitemap_enabled' => $indexable && (bool) ($definition['sitemap_enabled'] ?? false),
                'parent_page' => $definition['parent_page'] ?? null,
                'status' => $definition['status'] ?? 'planned',
                'schema_type' => $definition['schema_type'] ?? null,
                'content_model_type' => $contentModel ? $contentModel::class : null,
                'content_model_id' => $contentModel?->id,
                'notes' => $definition['notes'] ?? null,
            ]
        );
    }

    private function syncHomepageContent(): void
    {
        if (! HomepageContent::storageReady()) {
            return;
        }

        HomepageContent::updateOrCreate(
            ['site_key' => HomepageContent::DEFAULT_SITE_KEY],
            [
                'hero_title' => 'Solar Flood Lights in Kenya',
                'hero_description' => 'Compare solar flood lights, solar street lights, motion sensor lights and outdoor solar lighting for Kenyan homes, compounds, farms and businesses.',
                'why_choose_title' => 'Why Buy Solar Flood Lights From Us?',
                'why_choose_intro' => 'Use a focused solar lighting catalogue instead of a generic marketplace. Compare wattage, battery notes, stock, price and installation use before buying.',
                'faq_title' => 'Solar Flood Light Buying Questions',
                'faq_intro' => 'Quick answers about price, wattage, runtime, delivery and installation planning.',
                'content_badge' => 'Solar Lighting Kenya Guide',
                'content_title' => 'Solar Flood Lights in Kenya',
                'content_intro' => 'Choose outdoor solar lights based on area size, brightness, battery capacity and mounting location.',
                'content_body' => implode('', [
                    '<h2>Solar flood lights in Kenya</h2>',
                    '<p>Solar flood lights are used for home compounds, gates, farms, parking yards, shops, schools, churches, warehouses and outdoor security areas where reliable lighting is needed.</p>',
                    '<p>Use the catalogue to compare current prices, stock status, wattage, battery notes and product categories before choosing a solar light.</p>',
                    '<h3>Core solar lighting categories</h3>',
                    '<ul>',
                    '<li><a href="/solar-flood-lights">Solar flood lights</a> for compounds, yards, farms and security areas.</li>',
                    '<li><a href="/solar-street-lights">Solar street lights</a> for roads, estates, institutions and parking areas.</li>',
                    '<li><a href="/solar-motion-sensor-lights">Motion sensor solar lights</a> for entrances, walkways and energy-saving security lighting.</li>',
                    '</ul>',
                    '<h3>What to consider before buying</h3>',
                    '<p>Check wattage, lumen output, battery capacity, panel size, charging time, weather rating, mounting height, motion sensor needs and expected night runtime.</p>',
                ]),
                'why_choose_items' => [
                    ['title' => 'Focused Solar Lighting', 'description' => 'The site architecture prioritizes solar flood lights, street lights, security lights and outdoor solar lighting in Kenya.'],
                    ['title' => 'Current Catalogue Prices', 'description' => 'Product prices come from catalogue records, keeping product cards, product pages and schema aligned.'],
                    ['title' => 'Clear Buying Paths', 'description' => 'Commercial, category, guide and location pages are separated to avoid keyword cannibalization.'],
                    ['title' => 'No Thin Filter Pages', 'description' => 'Filter and search URLs are noindex and excluded from XML sitemaps.'],
                    ['title' => 'Product-Level Details', 'description' => 'Product pages support specifications, stock status, warranty notes, delivery notes and related products.'],
                    ['title' => 'Ready for Scale', 'description' => 'The registry supports future wattage, brand, location and application pages without auto-indexing weak content.'],
                ],
                'faq_items' => [
                    ['question' => 'Are prices on the website current?', 'answer' => 'Product prices are generated from the store catalogue and should update when the admin changes a product price.'],
                    ['question' => 'Can I compare solar flood lights before buying?', 'answer' => 'Use category pages and product pages to compare price, stock status, wattage, battery notes and recommended applications.'],
                    ['question' => 'Do product pages show stock status?', 'answer' => 'Yes. Each product page shows whether the product is currently listed as available or out of stock.'],
                    ['question' => 'Why are some SEO pages noindex?', 'answer' => 'Pages that need real products, local delivery details or verified service information stay noindex until that content is available.'],
                ],
            ]
        );
    }
}
