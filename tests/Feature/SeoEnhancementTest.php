<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Vendor;
use App\Support\SolarFloodLightSeoCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeoEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.canonical_url' => 'https://solarfloodlights.co.ke']);
    }

    public function test_product_page_outputs_dynamic_metadata_and_schema(): void
    {
        $product = $this->createProduct([
            'name' => 'ALLTOP 200W Solar Flood Light',
            'slug' => 'alltop-200w-solar-flood-light',
            'sku' => 'ALLTOP-200W',
            'model_number' => 'ALLTOP 200W Solar Flood Light',
            'brand' => 'ALLTOP',
            'price' => '12500.00',
            'faq_items' => [
                ['question' => 'Does it include a battery?', 'answer' => 'Confirm the package contents on the product page before ordering.'],
            ],
        ]);

        $response = $this->get('/product/'.$product->slug);

        $response->assertOk();
        $response->assertSee('ALLTOP 200W Solar Flood Light Price in Kenya', false);
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"priceCurrency":"KES"', false);
        $response->assertSee('"price":"12500.00"', false);
        $response->assertSee('"availability":"https://schema.org/InStock"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('Who is this product best for?');
        $response->assertSee('Does it include a battery?');
    }

    public function test_flood_light_authority_category_has_dynamic_price_table_and_faq_schema(): void
    {
        $category = Category::create([
            'name' => 'Solar Flood Lights Price in Kenya',
            'slug' => SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG,
            'meta_description' => 'Solar flood light price guide.',
            'description' => '<p>Solar flood light buying guide.</p>',
        ]);

        $product = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'ALLTOP 200W Solar Flood Light',
            'slug' => 'alltop-200w-solar-flood-light',
            'sku' => 'ALLTOP-200W',
            'price' => '12500.00',
        ]);

        $response = $this->get('/category/'.SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG);

        $response->assertOk();
        $response->assertSee('Solar flood light price list in Kenya');
        $response->assertSee(route('product.show', $product), false);
        $response->assertSee('KSh 12,500.00');
        $response->assertSee('How to choose a solar flood light');
        $response->assertSee('"@type":"FAQPage"', false);
    }

    public function test_legacy_category_redirects_to_primary_flood_light_page(): void
    {
        Category::create([
            'name' => 'Solar Flood Lights Price in Kenya',
            'slug' => SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG,
        ]);

        Category::create([
            'name' => 'Solar floodlights',
            'slug' => 'solar-floodlights',
        ]);

        $this->get('/category/solar-floodlights')
            ->assertRedirect('/category/'.SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG);
    }

    public function test_broad_solar_category_uses_relevant_catalog_fallback(): void
    {
        $broadCategory = Category::create([
            'name' => 'Solar Lights',
            'slug' => 'solar-lights',
            'description' => '<p>All solar lighting product groups.</p>',
        ]);

        $floodCategory = Category::create([
            'name' => 'Solar Flood Lights Price in Kenya',
            'slug' => SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG,
        ]);

        $product = $this->createProduct([
            'category_id' => $floodCategory->id,
            'name' => 'ALLTOP 200W Solar Flood Light',
            'slug' => 'alltop-200w-solar-flood-light',
            'sku' => 'ALLTOP-200W',
        ]);

        $response = $this->get('/category/'.$broadCategory->slug);

        $response->assertOk();
        $response->assertSee('Showing relevant solar lighting products from the wider catalogue');
        $response->assertSee($product->name);
        $response->assertDontSee('No products found.');
    }

    public function test_sitemap_includes_canonical_public_urls_and_excludes_private_paths(): void
    {
        $category = Category::create([
            'name' => 'Solar Flood Lights',
            'slug' => 'solar-flood-lights',
        ]);

        $product = $this->createProduct([
            'category_id' => $category->id,
            'name' => '100W Solar Flood Light',
            'slug' => '100w-solar-flood-light',
            'sku' => 'SFL-100W',
        ]);

        Page::create([
            'meta_title' => 'Solar Flood Light Buying Guide',
            'meta_description' => 'A buying guide for solar flood lights in Kenya.',
            'title' => 'Solar Flood Light Buying Guide',
            'heading_two' => 'Solar Flood Lights',
            'slug' => 'solar-flood-light-buying-guide',
            'type' => 'post',
            'body' => '<p>Solar flood light buying guidance.</p>',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('https://solarfloodlights.co.ke/category/solar-flood-lights', false);
        $response->assertSee('https://solarfloodlights.co.ke/product/'.$product->slug, false);
        $response->assertSee('https://solarfloodlights.co.ke/solar-flood-light-buying-guide', false);
        $response->assertDontSee('/admin', false);
        $response->assertDontSee('/checkout', false);
    }

    public function test_trust_page_fallback_exists_without_inventing_contact_details(): void
    {
        $this->get('/pages/contact-us')
            ->assertRedirect('/contact-us');

        $response = $this->get('/contact-us');

        $response->assertOk();
        $response->assertSee('Contact Solar Flood Lights Kenya');
        $response->assertSee('<link rel="canonical" href="https://solarfloodlights.co.ke/contact-us">', false);
        $response->assertDontSee('Official'.' Solar Flood Lights'.' Store');
    }

    public function test_known_comparison_page_requires_both_products_and_links_to_them(): void
    {
        $category = Category::create([
            'name' => 'Solar Flood Lights Price in Kenya',
            'slug' => SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG,
        ]);

        $product100w = $this->createProduct([
            'category_id' => $category->id,
            'name' => '100W Solar Flood Light',
            'slug' => '100w-solar-flood-light',
            'sku' => 'SFL-100W',
        ]);

        $product200w = $this->createProduct([
            'category_id' => $category->id,
            'name' => '200W Solar Flood Light',
            'slug' => '200w-solar-flood-light',
            'sku' => 'SFL-200W',
        ]);

        $response = $this->get('/compare/100w-vs-200w-solar-flood-light');

        $response->assertOk();
        $response->assertSee('100W vs 200W Solar Flood Light');
        $response->assertSee(route('product.show', $product100w), false);
        $response->assertSee(route('product.show', $product200w), false);
    }

    public function test_seo_audit_command_is_safe_to_run(): void
    {
        $exitCode = Artisan::call('seo:audit');

        $this->assertSame(0, $exitCode);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        $category = Category::find($attributes['category_id'] ?? null)
            ?? Category::first()
            ?? Category::create([
                'name' => 'Solar Flood Lights',
                'slug' => SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG,
            ]);

        $vendorUser = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'phone' => '0712345678',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Solar Flood Lights Kenya Store '.$vendorUser->id,
            'slug' => 'solar-flood-lights-kenya-store-'.$vendorUser->id,
            'description' => 'Solar lighting products.',
            'phone' => '0712345678',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        $product = Product::create(array_merge([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Solar Flood Light',
            'slug' => 'solar-flood-light',
            'description' => '<p>Reliable solar lighting for Kenyan homes and businesses.</p>',
            'meta_description' => null,
            'price' => '12000.00',
            'stock' => 5,
            'sku' => 'SFL-DEFAULT',
            'status' => 'active',
        ], $attributes, [
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]));

        ProductImage::create([
            'product_id' => $product->id,
            'image_url' => 'https://example.com/product.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        return $product;
    }
}
