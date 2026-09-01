<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomepageContent;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\MikrotikSeoCatalog;
use App\Support\ProductContent;
use App\Support\ProductImageCatalog;
use App\Support\ProductSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPagePolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbpoe_resolves_official_mikrotik_image(): void
    {
        $this->assertSame(
            'https://cdn.mikrotik.com/web-assets/rb_images/848_lg.webp',
            ProductImageCatalog::officialUrlFor('MikroTik RBPoE')
        );
        $this->assertSame(
            'https://cdn.mikrotik.com/web-assets/rb_images/848_lg.webp',
            ProductImageCatalog::officialUrlFor('rbpoe')
        );
    }

    public function test_display_name_trims_trailing_dashes(): void
    {
        $product = $this->createProduct([
            'name' => 'RBPOE –',
            'slug' => 'rbpoe',
            'sku' => 'RBPOE',
        ]);

        $this->assertSame('RBPOE', ProductSeo::displayName($product));
        $this->assertSame('RBPOE', ProductSeo::model($product));

        $response = $this->get('/product/'.$product->slug);

        $response->assertOk();
        $response->assertSee('<h1 class="product-page-title">RBPOE</h1>', false);
        $response->assertDontSee('RBPOE –', false);
    }

    public function test_logo_url_is_null_when_file_is_missing(): void
    {
        $directory = public_path('uploads/homepage-content');
        if (is_dir($directory)) {
            \Illuminate\Support\Facades\File::cleanDirectory($directory);
        }

        HomepageContent::create([
            'site_key' => HomepageContent::DEFAULT_SITE_KEY,
            'hero_title' => 'Solar Flood Lights Kenya',
            'hero_description' => 'Solar lighting in Kenya.',
            'site_logo_path' => 'uploads/homepage-content/missing-logo.png',
        ]);

        $this->assertNull(HomepageContent::current()->siteLogoUrl());

        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('missing-logo.png', false);
        $response->assertSee('Solar Flood Lights Kenya');
    }

    public function test_logo_falls_back_to_an_existing_uploaded_logo_file(): void
    {
        $directory = public_path('uploads/homepage-content');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
        \Illuminate\Support\Facades\File::cleanDirectory($directory);

        \Illuminate\Support\Facades\File::put($directory.'/20260101-logo-recovered.png', 'png-bytes');

        HomepageContent::create([
            'site_key' => HomepageContent::DEFAULT_SITE_KEY,
            'hero_title' => 'MikroTik Kenya',
            'hero_description' => 'Networking equipment in Kenya.',
            'site_logo_path' => 'uploads/homepage-content/deleted-logo.png',
        ]);

        $logoUrl = HomepageContent::current()->siteLogoUrl();

        $this->assertNotNull($logoUrl);
        $this->assertStringContainsString('20260101-logo-recovered.png', $logoUrl);

        \Illuminate\Support\Facades\File::delete($directory.'/20260101-logo-recovered.png');
    }

    public function test_summary_truncates_at_word_boundary_with_ellipsis(): void
    {
        $text = str_repeat('word ', 30).'end';

        $summary = ProductContent::summary($text, 60);

        $this->assertStringEndsWith('…', $summary);
        $this->assertLessThanOrEqual(61, mb_strlen($summary));
        $this->assertStringNotContainsString('Ideal for Mikr', $summary);

        $this->assertSame('Short text', ProductContent::summary('Short text', 60));
        $this->assertSame('', ProductContent::summary(null, 60));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        $category = Category::create([
            'name' => 'MikroTik Router Prices in Kenya',
            'slug' => MikrotikSeoCatalog::ROUTER_AUTHORITY_SLUG,
        ]);

        $vendorUser = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'phone' => '0712345678',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Mikrotik Kenya Store '.$vendorUser->id,
            'slug' => 'mikrotik-kenya-store-'.$vendorUser->id,
            'description' => 'Network and routing equipment.',
            'phone' => '0712345678',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        return Product::create(array_merge([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'MikroTik Router',
            'slug' => 'mikrotik-router',
            'description' => '<p>Reliable routing hardware for Kenyan networks.</p>',
            'price' => '5000.00',
            'stock' => 10,
            'sku' => 'RB-TEST',
            'status' => 'active',
        ], $attributes));
    }
}
