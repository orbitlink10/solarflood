<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StorefrontSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_search_redirects_to_the_matching_product_page(): void
    {
        [$product] = $this->createCatalogProducts();

        $response = $this->get('/?search='.urlencode('RB5009UPr+S+IN'));

        $response->assertRedirect(route('product.show', $product));
    }

    public function test_partial_search_shows_filtered_catalog_results(): void
    {
        [$matchingProduct, $otherProduct] = $this->createCatalogProducts();

        $response = $this->get('/?search=5009');

        $response->assertOk();
        $response->assertSee('Results for "5009"', false);
        $response->assertSee($matchingProduct->name);
        $response->assertDontSee($otherProduct->name);
    }

    public function test_category_page_displays_saved_category_content(): void
    {
        $category = Category::create([
            'name' => 'Mikrotik Wired Routers Price in Kenya',
            'slug' => 'mikrotik-wired-routers-price-in-kenya',
            'meta_description' => 'Wired router price guide.',
            'description' => '<h2>RouterBOARD options</h2><p>Compare MikroTik wired routers for homes, businesses, and ISPs.</p><script>alert(1)</script>',
        ]);

        $vendorUser = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'phone' => '0712345678',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Mikrotik Kenya Store',
            'slug' => 'mikrotik-kenya-store',
            'description' => 'Network and routing equipment.',
            'phone' => '0712345678',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'RB4011 Business Router',
            'slug' => 'rb4011-business-router',
            'description' => '<p>Rackmount router for offices.</p>',
            'meta_description' => 'RB4011 router for Kenyan businesses.',
            'price' => '29000.00',
            'stock' => 4,
            'sku' => 'RB4011',
            'status' => 'active',
        ]);

        $response = $this->get(route('category.show', $category));

        $response->assertOk();
        $response->assertSee('Mikrotik Wired Routers Price in Kenya');
        $response->assertSee('Wired router price guide.');
        $response->assertSee('RB4011 Business Router');
        $response->assertSee('RouterBOARD options');
        $response->assertSee('Compare MikroTik wired routers for homes, businesses, and ISPs.');
        $response->assertDontSee('<script>alert(1)</script>', false);

        $content = $response->getContent();
        $summaryPosition = strpos($content, 'category-content-summary">Wired router price guide.');
        $productPosition = strpos($content, 'RB4011 Business Router');
        $descriptionPosition = strpos($content, 'RouterBOARD options');

        $this->assertNotFalse($summaryPosition);
        $this->assertNotFalse($productPosition);
        $this->assertNotFalse($descriptionPosition);
        $this->assertLessThan($productPosition, $summaryPosition);
        $this->assertLessThan($descriptionPosition, $productPosition);
    }

    public function test_catalog_uses_local_placeholder_when_product_has_no_image(): void
    {
        [, $product] = $this->createCatalogProducts();
        $product->images()->delete();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('/assets/product-placeholder.svg', false);
        $response->assertDontSee('via.placeholder.com', false);
    }

    public function test_catalog_uses_official_mikrotik_image_when_known_product_has_no_image(): void
    {
        [$product] = $this->createCatalogProducts();
        $product->images()->delete();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('https://cdn.mikrotik.com/web-assets/rb_images/2190_lg.webp', false);
        $response->assertDontSee('via.placeholder.com', false);
    }

    public function test_catalog_renders_legacy_relative_product_image_paths(): void
    {
        [$product] = $this->createCatalogProducts();
        $product->images()->update(['image_url' => 'uploads/products/rb5009.jpg']);

        $uploadedPath = public_path('uploads/products/rb5009.jpg');
        File::ensureDirectoryExists(dirname($uploadedPath));
        File::put($uploadedPath, 'test image');

        try {
            $response = $this->get('/');

            $response->assertOk();
            $response->assertSee('src="/uploads/products/rb5009.jpg"', false);
            $response->assertDontSee('src="/assets/product-placeholder.svg"', false);
        } finally {
            File::delete($uploadedPath);
        }
    }

    public function test_catalog_ignores_missing_local_product_image_and_uses_official_fallback(): void
    {
        [$product] = $this->createCatalogProducts();
        $product->images()->update(['image_url' => 'uploads/products/missing-rb5009.jpg']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('src="https://cdn.mikrotik.com/web-assets/rb_images/2190_lg.webp"', false);
        $response->assertDontSee('src="/uploads/products/missing-rb5009.jpg"', false);
    }

    public function test_catalog_uses_preuploaded_product_file_matching_the_product_slug(): void
    {
        [$product] = $this->createCatalogProducts();
        $product->images()->delete();

        $uploadedPath = public_path('uploads/products/'.$product->slug.'.jpg');
        File::ensureDirectoryExists(dirname($uploadedPath));
        File::put($uploadedPath, 'test image');

        try {
            $response = $this->get('/');

            $response->assertOk();
            $response->assertSee('src="/uploads/products/'.$product->slug.'.jpg"', false);
            $response->assertDontSee('src="/assets/product-placeholder.svg"', false);
        } finally {
            File::delete($uploadedPath);
        }
    }

    public function test_catalog_can_render_product_uploads_stored_under_laravel_storage(): void
    {
        [$product] = $this->createCatalogProducts();
        $product->images()->update(['image_url' => 'uploads/products/storage-rb5009.jpg']);

        $storagePath = storage_path('app/public/uploads/products/storage-rb5009.jpg');
        File::ensureDirectoryExists(dirname($storagePath));
        File::put($storagePath, 'test image');

        try {
            $response = $this->get('/');

            $response->assertOk();
            $response->assertSee('src="/uploads/products/storage-rb5009.jpg"', false);
            $response->assertDontSee('src="/assets/product-placeholder.svg"', false);

            $this->get('/uploads/products/storage-rb5009.jpg')->assertOk();
        } finally {
            File::delete($storagePath);
        }
    }

    public function test_homepage_shows_two_flood_light_product_rows_from_authority_category(): void
    {
        $vendorUser = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'phone' => '0712345678',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Solar Flood Lights Kenya Store',
            'slug' => 'solar-flood-lights-kenya-store',
            'description' => 'Solar lighting products.',
            'phone' => '0712345678',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        $floodCategory = Category::create([
            'name' => 'Solar Flood Lights Price in Kenya',
            'slug' => 'solar-flood-lights',
        ]);

        $otherCategory = Category::create([
            'name' => 'Solar Street Lights',
            'slug' => 'solar-street-lights',
        ]);

        for ($index = 1; $index <= 9; $index++) {
            Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $floodCategory->id,
                'name' => 'Solar Flood Light Model '.$index,
                'slug' => 'solar-flood-light-model-'.$index,
                'description' => '<p>Flood light model '.$index.' for homes and offices.</p>',
                'meta_description' => 'Solar flood light model '.$index.' price in Kenya.',
                'price' => (string) (10000 + $index),
                'stock' => 5,
                'sku' => 'FLOOD-'.$index,
                'status' => 'active',
                'created_at' => now()->addMinutes($index),
                'updated_at' => now()->addMinutes($index),
            ]);
        }

        Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $otherCategory->id,
            'name' => 'Solar Street Light Outside Category',
            'slug' => 'solar-street-light-outside-category',
            'description' => '<p>Street light product.</p>',
            'meta_description' => 'Street light product outside the flood light price category.',
            'price' => '22000.00',
            'stock' => 5,
            'sku' => 'STREET-1',
            'status' => 'active',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('products-grid--router-rows', false);
        $response->assertSee('home-product-section--full-width', false);
        $response->assertSee('products-grid--catalog', false);
        $content = $response->getContent();
        $featuredStart = strpos($content, 'products-grid products-grid--router-rows');

        $this->assertNotFalse($featuredStart);

        $featuredEnd = strpos($content, '</section>', $featuredStart);
        $this->assertNotFalse($featuredEnd);

        $featuredSection = substr($content, $featuredStart, $featuredEnd - $featuredStart);

        $this->assertSame(6, substr_count($featuredSection, 'class="product-card"'));
        $this->assertStringContainsString('Solar Flood Light Model 9', $featuredSection);
        $this->assertStringContainsString('Solar Flood Light Model 4', $featuredSection);
        $this->assertStringNotContainsString('Solar Flood Light Model 3', $featuredSection);
        $response->assertSee('Solar Flood Light Model 3');
        $response->assertSee('Solar Flood Light Model 1');
        $response->assertSee('Solar Street Light Outside Category');
        $response->assertSee('KES 10,009.00');
        $response->assertSee('product-desc', false);
        $response->assertDontSee('Page 1 of', false);
    }

    public function test_homepage_keeps_flood_light_rows_when_authority_category_uses_legacy_slug(): void
    {
        $vendorUser = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'phone' => '0712345678',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Solar Flood Lights Kenya Store',
            'slug' => 'solar-flood-lights-kenya-store',
            'description' => 'Solar lighting products.',
            'phone' => '0712345678',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        $floodCategory = Category::create([
            'name' => 'solar floodlights',
            'slug' => 'solar-floodlights',
        ]);

        Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $floodCategory->id,
            'name' => 'Solar Flood Light Model',
            'slug' => 'solar-flood-light-model',
            'description' => '<p>Flood light model for homes and offices.</p>',
            'meta_description' => 'Solar flood light model price in Kenya.',
            'price' => '10000.00',
            'stock' => 5,
            'sku' => 'FLOOD-LEGACY',
            'status' => 'active',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('products-grid--router-rows', false);
        $response->assertSee('href="http://localhost/category/solar-flood-lights"', false);
        $response->assertSee('Solar Flood Light Model');
    }

    public function test_primary_flood_light_price_url_renders_when_only_legacy_category_exists(): void
    {
        $vendorUser = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'phone' => '0712345678',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Solar Flood Lights Kenya Store',
            'slug' => 'solar-flood-lights-kenya-store',
            'description' => 'Solar lighting products.',
            'phone' => '0712345678',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        $floodCategory = Category::create([
            'name' => 'solar floodlights',
            'slug' => 'solar-floodlights',
        ]);

        Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $floodCategory->id,
            'name' => 'Solar Flood Light Model',
            'slug' => 'solar-flood-light-model',
            'description' => '<p>Flood light model for homes and offices.</p>',
            'meta_description' => 'Solar flood light model price in Kenya.',
            'price' => '10000.00',
            'stock' => 5,
            'sku' => 'FLOOD-LEGACY',
            'status' => 'active',
        ]);

        $response = $this->get('/category/solar-flood-lights');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="https://solarfloodlights.co.ke/category/solar-flood-lights">', false);
        $response->assertSee('Solar Flood Light Model');
    }

    /**
     * @return array{0: \App\Models\Product, 1: \App\Models\Product}
     */
    private function createCatalogProducts(): array
    {
        $vendorUser = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'phone' => '0712345678',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Mikrotik Kenya Store',
            'slug' => 'mikrotik-kenya-store',
            'description' => 'Network and routing equipment.',
            'phone' => '0712345678',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        $category = Category::create([
            'name' => 'Mikrotik Ethernet Routers',
            'slug' => 'mikrotik-ethernet-routers',
        ]);

        $matchingProduct = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'RB5009UPr+S+IN',
            'slug' => 'rb5009uprsin',
            'description' => '<p>Enterprise router with PoE support.</p>',
            'meta_description' => 'High-performance MikroTik router for ISP and business use.',
            'price' => '56000.00',
            'compare_at_price' => '57000.00',
            'stock' => 3,
            'sku' => 'RB5009UPr+S+IN',
            'status' => 'active',
        ]);

        $otherProduct = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'CRS326-24G-2S+RM',
            'slug' => 'crs326-24g-2s-rm',
            'description' => '<p>Rackmount switch.</p>',
            'meta_description' => 'Managed gigabit switch.',
            'price' => '31000.00',
            'stock' => 4,
            'sku' => 'CRS326-24G-2S+RM',
            'status' => 'active',
        ]);

        ProductImage::create([
            'product_id' => $matchingProduct->id,
            'image_url' => 'https://example.com/rb5009.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        ProductImage::create([
            'product_id' => $otherProduct->id,
            'image_url' => 'https://example.com/crs326.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        return [$matchingProduct, $otherProduct];
    }
}
