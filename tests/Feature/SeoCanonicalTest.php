<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoCanonicalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.canonical_url' => 'https://solarfloodlights.co.ke']);
    }

    public function test_homepage_has_self_referencing_canonical_url(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<title>Solar Flood Lights Kenya | Best Prices &amp; Fast Delivery</title>', false);
        $response->assertSee('<link rel="canonical" href="https://solarfloodlights.co.ke/">', false);
    }

    public function test_search_results_are_noindexed_and_canonical_to_catalog_root(): void
    {
        $this->createProduct([
            'name' => 'RB5009UPr+S+IN',
            'slug' => 'rb5009uprsin',
            'sku' => 'RB5009UPr+S+IN',
        ]);

        $response = $this->get('/?search=5009');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="https://solarfloodlights.co.ke/">', false);
        $response->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_category_pagination_has_canonical_page_url(): void
    {
        $category = Category::create([
            'name' => 'Mikrotik Ethernet Routers',
            'slug' => 'mikrotik-ethernet-routers',
        ]);

        for ($index = 1; $index <= 25; $index++) {
            $this->createProduct([
                'category_id' => $category->id,
                'name' => 'Router Model '.$index,
                'slug' => 'router-model-'.$index,
                'sku' => 'ROUTER-'.$index,
            ]);
        }

        $response = $this->get('/category/mikrotik-ethernet-routers?page=2');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="https://solarfloodlights.co.ke/category/mikrotik-ethernet-routers?page=2">', false);
    }

    public function test_product_and_public_page_have_canonical_urls(): void
    {
        $product = $this->createProduct([
            'name' => 'MikroTik RB4011iGS+RM',
            'slug' => 'mikrotik-rb4011igsrm',
            'sku' => 'RB4011',
        ]);

        $page = Page::create([
            'meta_title' => 'Starlink in Kenya',
            'meta_description' => 'A guide to Starlink internet in Kenya.',
            'title' => 'Starlink in Kenya',
            'slug' => 'starlink-in-kenya',
            'type' => 'post',
            'body' => '<p>Reliable rural connectivity.</p>',
        ]);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://solarfloodlights.co.ke/product/mikrotik-rb4011igsrm">', false);

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://solarfloodlights.co.ke/starlink-in-kenya">', false);

        $this->get('/pages/'.$page->slug)
            ->assertStatus(301)
            ->assertRedirect('/'.$page->slug);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        $category = Category::find($attributes['category_id'] ?? null)
            ?? Category::first()
            ?? Category::create([
                'name' => 'Mikrotik Routers',
                'slug' => 'mikrotik-routers',
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

        $product = Product::create(array_merge([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Mikrotik Router',
            'slug' => 'mikrotik-router',
            'description' => '<p>Reliable routing hardware for Kenyan networks.</p>',
            'meta_description' => 'Reliable routing hardware for Kenyan networks.',
            'price' => '20000.00',
            'stock' => 5,
            'sku' => 'ROUTER',
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
