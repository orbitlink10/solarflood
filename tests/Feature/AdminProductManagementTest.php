<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomepageContent;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_sidebar_dashboard_structure(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Admin Overview');
        $response->assertSee('Dashboard');
        $response->assertSee('Content Management');
        $response->assertSee('Recent Orders');
        $response->assertDontSee('Post Product');
    }

    public function test_admin_can_view_management_index_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        Category::create([
            'name' => 'Networking',
            'slug' => 'networking',
        ]);

        Page::create([
            'title' => 'Landing Page',
            'slug' => 'landing-page',
            'type' => 'page',
        ]);

        Order::create([
            'user_id' => $admin->id,
            'order_number' => 'ORD-1001',
            'status' => 'pending',
            'total_amount' => 1000,
            'shipping_name' => 'Admin User',
            'shipping_email' => 'admin@example.com',
            'shipping_phone' => '0700000000',
            'shipping_address' => 'Nairobi',
            'payment_method' => 'cash_on_delivery',
        ]);

        $this->actingAs($admin)->get('/admin/categories')->assertOk()->assertSee('Categories');
        $this->actingAs($admin)->get('/admin/products')->assertOk()->assertSee('Products');
        $this->actingAs($admin)->get('/admin/orders')->assertOk()->assertSee('Orders');
        $this->actingAs($admin)->get('/admin/pages')->assertOk()->assertSee('Pages');
        $this->actingAs($admin)->get('/admin/pages-content')->assertOk()->assertSee('Update Homepage Content');
        $this->actingAs($admin)->get('/admin/testimonials')->assertOk()->assertSee('Testimonials');
    }

    public function test_admin_category_create_page_displays_requested_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->get('/admin/categories/create');

        $response->assertOk();
        $response->assertSee('Create Category');
        $response->assertSee('Meta description');
        $response->assertSee('Description (Optional)');
        $response->assertSee('Parent Category and Image (Optional)');
        $response->assertSee('Upload Image');
    }

    public function test_admin_can_create_category_with_meta_and_description(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $longDescription = '<p>'.str_repeat('A', 6005).'</p>';
        $image = UploadedFile::fake()->create('category.jpg', 64, 'image/jpeg');

        $response = $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Networking Guides',
            'meta_description' => 'Helpful networking category summaries for search and navigation.',
            'description' => $longDescription,
            'image' => $image,
        ]);

        $response->assertRedirect('/admin/categories');
        $response->assertSessionHas('success');

        $category = Category::query()->where('slug', 'networking-guides')->first();

        $this->assertNotNull($category);
        $this->assertSame($longDescription, $category->description);
        $this->assertSame('Helpful networking category summaries for search and navigation.', $category->meta_description);
        $this->assertNotNull($category->image_url);
        $this->assertStringStartsWith('/uploads/categories/', $category->image_url);

        $uploadedPath = public_path(ltrim((string) $category->image_url, '/\\'));
        $this->assertFileExists($uploadedPath);

        File::delete($uploadedPath);
    }

    public function test_admin_can_create_category_when_content_columns_are_missing(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['meta_description', 'description']);
        });

        $response = $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Switches',
            'meta_description' => 'This should not crash when columns are missing.',
            'description' => '<p>Still create the category.</p>',
        ]);

        $response->assertRedirect('/admin/categories');
        $response->assertSessionHas('success', 'Category saved. Run php artisan migrate to enable category meta description and description storage.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Switches',
            'slug' => 'switches',
        ]);
    }

    public function test_admin_categories_index_renders_working_preview_update_and_delete_actions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $category = Category::create([
            'name' => 'Business Routers',
            'slug' => 'business-routers',
        ]);

        $response = $this->actingAs($admin)->get('/admin/categories');

        $response->assertOk();
        $response->assertSee('Preview');
        $response->assertSee(route('category.show', $category), false);
        $response->assertSee(route('admin.categories.edit', $category), false);
        $response->assertSee(route('admin.categories.destroy', $category), false);
    }

    public function test_admin_subcategories_index_renders_working_preview_update_and_delete_actions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $parent = Category::create([
            'name' => 'Routers',
            'slug' => 'routers',
        ]);

        $subcategory = Category::create([
            'name' => 'Wired Routers',
            'slug' => 'wired-routers',
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/sub-categories');

        $response->assertOk();
        $response->assertSee('Preview');
        $response->assertSee(route('category.show', $subcategory), false);
        $response->assertSee(route('admin.categories.edit', $subcategory), false);
        $response->assertSee(route('admin.categories.destroy', $subcategory), false);
    }

    public function test_admin_product_create_page_displays_form(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->get('/admin/products/create');

        $response->assertOk();
        $response->assertSee('Add Product');
        $response->assertSee('Product Name');
        $response->assertSee('Marked Price (KES)');
        $response->assertSee('Subcategory');
        $response->assertSee('Description');
        $response->assertSee('Heading 1');
        $response->assertSee('Heading 6');
        $response->assertSee('Strikethrough');
        $response->assertSee('Align center');
    }

    public function test_admin_products_index_renders_working_preview_update_and_delete_actions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $category = Category::create([
            'name' => 'Routers',
            'slug' => 'routers',
        ]);

        $vendor = Vendor::create([
            'user_id' => $admin->id,
            'shop_name' => 'Admin Store',
            'slug' => 'admin-store',
            'description' => 'Products managed by admin.',
            'phone' => '0700000000',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'RB5009',
            'slug' => 'rb5009',
            'description' => '<p>Managed switch router.</p>',
            'meta_description' => 'RB5009 router listing.',
            'price' => '56000.00',
            'stock' => 3,
            'sku' => 'SKU-RB5009',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/products');

        $response->assertOk();
        $response->assertSee(route('product.show', $product), false);
        $response->assertSee(route('admin.products.edit', $product), false);
        $response->assertSee(route('admin.products.destroy', $product), false);
        $response->assertDontSee('Slug');
    }

    public function test_admin_page_create_page_displays_requested_post_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->get('/admin/pages/create');

        $response->assertOk();
        $response->assertSee('Manage Pages');
        $response->assertSee('Fill in the page details below to publish new content');
        $response->assertSee('Meta Title');
        $response->assertSee('Meta Description');
        $response->assertSee('Page Title');
        $response->assertSee('Heading 2');
        $response->assertSee('Page Description');
        $response->assertSee('Optional Slug and Image');
    }

    public function test_admin_can_post_products_from_admin_create_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $image = UploadedFile::fake()->create('camera.jpg', 64, 'image/jpeg');

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Admin Camera',
            'category_id' => $category->id,
            'description' => '<p>Camera added by <strong>admin</strong>.</p><script>alert(1)</script>',
            'meta_description' => 'Compact admin camera listing.',
            'price' => '499.99',
            'compare_at_price' => '579.99',
            'stock' => 8,
            'image' => $image,
        ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');

        $vendor = Vendor::query()->where('user_id', $admin->id)->first();

        $this->assertNotNull($vendor);
        $this->assertTrue((bool) $vendor->is_approved);

        $product = Product::query()->first();

        $this->assertNotNull($product);
        $this->assertSame($vendor->id, $product->vendor_id);
        $this->assertSame('active', $product->status);
        $this->assertSame('Compact admin camera listing.', $product->meta_description);
        $this->assertSame('<p>Camera added by <strong>admin</strong>.</p>', $product->description);
        $this->assertSame('579.99', $product->compare_at_price);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Admin Camera',
            'category_id' => $category->id,
            'meta_description' => 'Compact admin camera listing.',
            'compare_at_price' => '579.99',
        ]);

        $productImage = ProductImage::query()->where('product_id', $product->id)->first();

        $this->assertNotNull($productImage);
        $this->assertStringStartsWith('/uploads/products/', (string) $productImage->image_url);

        $uploadedPath = public_path(ltrim((string) $productImage->image_url, '/\\'));
        $this->assertFileExists($uploadedPath);

        File::delete($uploadedPath);
    }

    public function test_admin_can_create_a_new_category_while_posting_a_product(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Router Board',
            'category_name' => 'Networking',
            'description' => '<p>Managed by admin.</p>',
            'meta_description' => 'Networking product from the admin dashboard.',
            'price' => '12999.00',
            'stock' => 4,
        ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');

        $category = Category::query()->where('name', 'Networking')->first();
        $product = Product::query()->where('name', 'Router Board')->first();

        $this->assertNotNull($category);
        $this->assertNotNull($product);
        $this->assertSame($category->id, $product->category_id);

        $this->assertDatabaseHas('categories', [
            'name' => 'Networking',
            'slug' => 'networking',
        ]);
    }

    public function test_admin_can_assign_product_to_selected_subcategory(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $category = Category::create([
            'name' => 'Networking',
            'slug' => 'networking',
        ]);

        $subcategory = Category::create([
            'name' => 'Routers',
            'slug' => 'routers',
            'parent_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Core Router',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'description' => '<p>Enterprise router.</p>',
            'meta_description' => 'Enterprise-grade router for branch and core networks.',
            'price' => '74999.00',
            'compare_at_price' => '81999.00',
            'stock' => 2,
        ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');

        $product = Product::query()->where('name', 'Core Router')->first();

        $this->assertNotNull($product);
        $this->assertSame($subcategory->id, $product->category_id);
        $this->assertSame('81999.00', $product->compare_at_price);
    }

    public function test_admin_can_delete_product_from_admin_products_index(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $category = Category::create([
            'name' => 'Switches',
            'slug' => 'switches',
        ]);

        $vendor = Vendor::create([
            'user_id' => $admin->id,
            'shop_name' => 'Admin Store',
            'slug' => 'admin-store',
            'description' => 'Products managed by admin.',
            'phone' => '0700000000',
            'address' => 'Nairobi',
            'is_approved' => true,
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'CRS326',
            'slug' => 'crs326',
            'description' => '<p>Core switch.</p>',
            'meta_description' => 'CRS326 switch listing.',
            'price' => '31000.00',
            'stock' => 5,
            'sku' => 'SKU-CRS326',
            'status' => 'active',
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_url' => '/uploads/products/crs326.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Product deleted successfully.');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
    }

    public function test_admin_can_create_content_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->post('/admin/pages', [
            'meta_title' => 'Support Page Meta Title',
            'meta_description' => 'Support page meta description for search and content previews.',
            'title' => 'Support Page',
            'heading_two' => 'Support Options',
            'type' => 'page',
            'alt_text' => 'Support page hero image',
            'body' => '<p>Support content</p><script>alert(1)</script><pre><code>safe code</code></pre>',
        ]);

        $response->assertRedirect('/admin/pages');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'title' => 'Support Page',
            'slug' => 'support-page',
            'type' => 'page',
            'meta_title' => 'Support Page Meta Title',
            'meta_description' => 'Support page meta description for search and content previews.',
            'heading_two' => 'Support Options',
            'alt_text' => 'Support page hero image',
        ]);

        $page = Page::query()->where('slug', 'support-page')->first();

        $this->assertNotNull($page);
        $this->assertSame('<p>Support content</p><pre><code>safe code</code></pre>', $page->body);
    }

    public function test_admin_pages_index_renders_working_preview_update_and_delete_actions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $page = Page::create([
            'meta_title' => 'Preview Meta Title',
            'meta_description' => 'Preview page meta description.',
            'title' => 'Preview Page',
            'heading_two' => 'Preview Section',
            'slug' => 'preview-page',
            'type' => 'post',
            'alt_text' => 'Preview page image',
            'body' => '<p>Preview body content.</p>',
        ]);

        $response = $this->actingAs($admin)->get('/admin/pages');

        $response->assertOk();
        $response->assertSee(route('pages.show', ['page' => $page->slug]), false);
        $response->assertSee(route('admin.pages.edit', $page), false);
        $response->assertSee(route('admin.pages.destroy', $page), false);
    }

    public function test_admin_can_update_page_from_admin_editor(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $page = Page::create([
            'meta_title' => 'Original Meta Title',
            'meta_description' => 'Original page meta description.',
            'title' => 'Original Page',
            'heading_two' => 'Original Section',
            'slug' => 'original-page',
            'type' => 'post',
            'image_url' => 'https://example.com/original.jpg',
            'alt_text' => 'Original image',
            'body' => '<p>Original body content.</p>',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'meta_title' => 'Updated Meta Title',
            'meta_description' => 'Updated page meta description for search.',
            'title' => 'Updated Support Page',
            'heading_two' => 'Updated Section',
            'slug' => '',
            'type' => 'page',
            'image_url' => 'https://example.com/updated.jpg',
            'alt_text' => 'Updated image',
            'body' => '<p>Updated body content.</p><script>alert(1)</script>',
        ]);

        $response->assertRedirect('/admin/pages');
        $response->assertSessionHas('success', 'Page updated successfully.');

        $page->refresh();

        $this->assertSame('Updated Meta Title', $page->meta_title);
        $this->assertSame('Updated page meta description for search.', $page->meta_description);
        $this->assertSame('Updated Support Page', $page->title);
        $this->assertSame('Updated Section', $page->heading_two);
        $this->assertSame('updated-support-page', $page->slug);
        $this->assertSame('page', $page->type);
        $this->assertSame('https://example.com/updated.jpg', $page->image_url);
        $this->assertSame('Updated image', $page->alt_text);
        $this->assertSame('<p>Updated body content.</p>', $page->body);
    }

    public function test_admin_can_delete_page_from_admin_pages_index(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $page = Page::create([
            'meta_title' => 'Delete Meta Title',
            'meta_description' => 'Delete page meta description.',
            'title' => 'Delete Page',
            'heading_two' => 'Delete Section',
            'slug' => 'delete-page',
            'type' => 'post',
            'body' => '<p>Delete body content.</p>',
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/pages')
            ->delete(route('admin.pages.destroy', $page));

        $response->assertRedirect('/admin/pages');
        $response->assertSessionHas('success', 'Page deleted successfully.');
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_admin_page_preview_links_to_public_page_view(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $page = Page::create([
            'meta_title' => 'Preview Meta Title',
            'meta_description' => 'Preview page meta description.',
            'title' => 'Preview Page',
            'heading_two' => 'Preview Section',
            'slug' => 'preview-page',
            'type' => 'post',
            'alt_text' => 'Preview page image',
            'body' => '<p>Preview body content.</p>',
        ]);

        $this->actingAs($admin)
            ->get('/admin/pages')
            ->assertOk()
            ->assertSee(route('pages.show', ['page' => $page->slug]), false);

        $this->get('/pages/'.$page->slug)
            ->assertRedirect(route('pages.show', ['page' => $page->slug]));

        $this->get(route('pages.show', ['page' => $page->slug]))
            ->assertOk()
            ->assertSee('Preview Page')
            ->assertSee('Preview Section')
            ->assertSee('Preview body content.');
    }

    public function test_admin_pages_index_shows_clear_error_when_table_is_missing(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        Schema::drop('pages');

        $response = $this->actingAs($admin)->get('/admin/pages');

        $response->assertOk();
        $response->assertSee('Page storage is not ready yet.');
        $response->assertSee('php artisan migrate');
    }

    public function test_admin_page_create_shows_clear_error_when_table_is_missing(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        Schema::drop('pages');

        $response = $this->actingAs($admin)->get('/admin/pages/create');

        $response->assertOk();
        $response->assertSee('Page storage is not ready yet.');
        $response->assertSee('php artisan migrate');
    }

    public function test_admin_page_create_submit_shows_clear_error_when_table_is_missing(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        Schema::drop('pages');

        $response = $this->actingAs($admin)->post('/admin/pages', [
            'meta_title' => 'Support Page Meta Title',
            'meta_description' => 'Support page meta description for search and content previews.',
            'title' => 'Support Page',
            'heading_two' => 'Support Options',
            'type' => 'page',
            'alt_text' => 'Support page hero image',
            'body' => '<p>Support content</p>',
        ]);

        $response->assertRedirect('/admin/pages');
        $response->assertSessionHas('error', 'Page storage is not ready yet. Run php artisan migrate to create the pages table.');
    }

    public function test_admin_homepage_content_page_displays_extended_section_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->get('/admin/pages-content');

        $response->assertOk();
        $response->assertSee('Website Logo');
        $response->assertSee('Header Contact Details');
        $response->assertSee('Phone Number');
        $response->assertSee('WhatsApp Number');
        $response->assertSee('Why Choose Section');
        $response->assertDontSee('Testimonials Section');
        $response->assertSee('FAQ Section');
        $response->assertSee('Homepage Guide Content');
        $response->assertSee('Home Page Content');
        $response->assertSee('Only the content written in this editor is shown on the homepage guide section.');
        $response->assertSee('Format');
    }

    public function test_admin_can_update_homepage_content(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $logo = UploadedFile::fake()->create('mikrotik-logo.png', 64, 'image/png');

        $response = $this->actingAs($admin)->post('/admin/pages-content', [
            'hero_title' => 'Starlink Kenya for Homes and Business',
            'hero_description' => 'Deploy reliable satellite internet across homes, offices, remote sites, and branch networks from one storefront.',
            'contact_phone' => '+254 700 123 456',
            'contact_whatsapp' => '0711 222 333',
            'contact_email' => 'sales@mikrotikkenya.co.ke',
            'site_logo' => $logo,
        ]);

        $response->assertRedirect('/admin/pages-content');
        $response->assertSessionHas('success');

        $storedContent = HomepageContent::query()->where('site_key', HomepageContent::DEFAULT_SITE_KEY)->first();
        $this->assertNotNull($storedContent);
        $this->assertSame('Starlink Kenya for Homes and Business', $storedContent->hero_title);
        $this->assertSame('+254 700 123 456', $storedContent->contact_phone);
        $this->assertSame('0711 222 333', $storedContent->contact_whatsapp);
        $this->assertSame('sales@mikrotikkenya.co.ke', $storedContent->contact_email);
        $this->assertNotNull($storedContent->site_logo_path);
        $this->assertFileExists(public_path($storedContent->site_logo_path));

        $homeResponse = $this->get('/');
        $homeResponse->assertOk();
        $homeResponse->assertSee('Starlink Kenya for Homes and Business');
        $homeResponse->assertSee('Deploy reliable satellite internet across homes, offices, remote sites, and branch networks from one storefront.');
        $homeResponse->assertSee($storedContent->siteLogoUrl());
        $homeResponse->assertSee('href="tel:+254700123456"', false);
        $homeResponse->assertSee('Phone +254 700 123 456');
        $homeResponse->assertSee('href="mailto:sales@mikrotikkenya.co.ke"', false);
        $homeResponse->assertSee('Email sales@mikrotikkenya.co.ke');
        $homeResponse->assertSee('href="https://wa.me/254711222333"', false);
        $homeResponse->assertSee('aria-label="Chat with us on WhatsApp"', false);
        $homeResponse->assertDontSee('Login');
        $homeResponse->assertDontSee('Register');
        $homeResponse->assertDontSee('Cart (0)');

        File::delete(public_path($storedContent->site_logo_path));
    }

    public function test_admin_can_update_homepage_sections_and_render_them_on_homepage(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->post('/admin/pages-content', [
            'hero_title' => 'Starlink Kenya for Homes and Business',
            'hero_description' => 'Deploy reliable satellite internet across homes, offices, remote sites, and branch networks from one storefront.',
            'why_choose_title' => 'Why Choose Our Starlink Team?',
            'why_choose_intro' => 'Everything needed to buy, install, and support Starlink in Kenya from one local team.',
            'why_choose_items' => [
                ['title' => 'Official Reseller', 'description' => 'Authentic hardware and guided setup.'],
                ['title' => 'Nairobi Delivery', 'description' => 'Fast dispatch for urgent installations.'],
            ],
            'faq_badge' => 'FAQ',
            'faq_title' => 'Questions Before You Buy',
            'faq_intro' => 'The common things customers ask before installation.',
            'faq_items' => [
                ['question' => 'Do you install outside Nairobi?', 'answer' => 'Yes, installation support can be arranged outside Nairobi depending on the site and scope.'],
                ['question' => 'What comes with the kit?', 'answer' => 'The kit includes the dish, router, mounting hardware, power supply, and cables.'],
            ],
            'content_body' => '<h2>Installation Planning</h2><p>Choose a location with a clear view of the sky.</p><script>alert(1)</script><h3>Site Readiness</h3><p>Check power, roof access, and indoor Wi-Fi coverage before installation.</p>',
        ]);

        $response->assertRedirect('/admin/pages-content');
        $response->assertSessionHas('success', 'Homepage content updated successfully.');

        $homeResponse = $this->get('/');
        $homeResponse->assertOk();
        $homeResponse->assertSee('Why Choose Our Starlink Team?');
        $homeResponse->assertSee('Authentic hardware and guided setup.');
        $homeResponse->assertSee('Questions Before You Buy');
        $homeResponse->assertSee('Do you install outside Nairobi?');
        $homeResponse->assertSee('Installation Planning');
        $homeResponse->assertSee('Check power, roof access, and indoor Wi-Fi coverage before installation.');
        $homeResponse->assertDontSee('alert(1)');
    }

    public function test_homepage_guide_section_renders_only_the_rich_body_content(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($admin)->post('/admin/pages-content', [
            'hero_title' => 'Starlink Kenya for Homes and Business',
            'hero_description' => 'Deploy reliable satellite internet across homes, offices, remote sites, and branch networks from one storefront.',
            'content_body' => '<h2>Guide Title From Editor</h2><p>Editable paragraph body.</p>',
        ]);

        $response->assertRedirect('/admin/pages-content');

        $storedContent = HomepageContent::query()->where('site_key', HomepageContent::DEFAULT_SITE_KEY)->first();
        $this->assertNotNull($storedContent);
        $storedContent->update([
            'content_badge' => 'Legacy Guide Badge',
            'content_title' => 'Legacy Separate Guide Title',
            'content_intro' => 'Legacy separate intro should not render on the homepage.',
        ]);

        $homeResponse = $this->get('/');
        $homeResponse->assertOk();
        $homeResponse->assertSee('home-guide-body--scrollable', false);
        $homeResponse->assertSee('Guide Title From Editor');
        $homeResponse->assertSee('Editable paragraph body.');
        $homeResponse->assertDontSee('Legacy Guide Badge');
        $homeResponse->assertDontSee('Legacy Separate Guide Title');
        $homeResponse->assertDontSee('Legacy separate intro should not render on the homepage.');

        $editorResponse = $this->actingAs($admin)->get('/admin/pages-content');
        $editorResponse->assertOk();
        $editorResponse->assertDontSee('Homepage Guide Title');
        $editorResponse->assertDontSee('Guide Badge');
        $editorResponse->assertSee('Editable paragraph body.');
    }

    public function test_admin_testimonials_index_displays_settings_and_list_management(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        Testimonial::create([
            'name' => 'Mary K., Nyeri',
            'role' => 'Customer',
            'quote' => 'We live in a rural area where internet access was inconsistent until we installed Starlink.',
            'rating' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/testimonials');

        $response->assertOk();
        $response->assertSee('Homepage Testimonial Settings');
        $response->assertSee('Testimonial List');
        $response->assertSee('+ Add Testimonial');
        $response->assertSee('Mary K., Nyeri');
    }

    public function test_admin_can_create_update_and_delete_testimonial(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        $createResponse = $this->actingAs($admin)->post('/admin/testimonials', [
            'name' => 'James M., Techpreneur, Nairobi',
            'role' => 'Customer',
            'quote' => 'Our startup needed high-speed internet that would not slow the team down.',
            'rating' => 5,
            'sort_order' => 2,
            'is_active' => '1',
        ]);

        $createResponse->assertRedirect('/admin/testimonials');
        $createResponse->assertSessionHas('success', 'Testimonial added successfully.');

        $testimonial = Testimonial::query()->where('name', 'James M., Techpreneur, Nairobi')->first();
        $this->assertNotNull($testimonial);

        $showResponse = $this->actingAs($admin)->get(route('admin.testimonials.show', $testimonial));
        $showResponse->assertOk();
        $showResponse->assertSee('Testimonial Details');
        $showResponse->assertSee('Our startup needed high-speed internet');

        $updateResponse = $this->actingAs($admin)->put(route('admin.testimonials.update', $testimonial), [
            'name' => 'James M., Techpreneur, Nairobi',
            'role' => 'Verified Customer',
            'quote' => 'Our startup now runs meetings, demos, and uploads without the old delays.',
            'rating' => 4,
            'sort_order' => 1,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect('/admin/testimonials');
        $updateResponse->assertSessionHas('success', 'Testimonial updated successfully.');

        $testimonial->refresh();
        $this->assertSame('Verified Customer', $testimonial->role);
        $this->assertSame(4, $testimonial->rating);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.testimonials.destroy', $testimonial));
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success', 'Testimonial deleted successfully.');
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_admin_can_update_testimonial_settings_and_homepage_renders_testimonials_from_table(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        Testimonial::create([
            'name' => 'Peter N., Turkana',
            'role' => 'Customer',
            'quote' => 'As an NGO operating in remote areas, communication is much easier with a stable connection.',
            'rating' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/testimonials/settings', [
            'testimonials_badge' => 'Testimonials',
            'testimonials_title' => 'What Clients Say About Us',
            'testimonials_intro' => 'Stories from homes and businesses already using the service.',
        ]);

        $response->assertRedirect('/admin/testimonials');
        $response->assertSessionHas('success', 'Testimonial section settings updated successfully.');

        $homeResponse = $this->get('/');
        $homeResponse->assertOk();
        $homeResponse->assertSee('What Clients Say About Us');
        $homeResponse->assertSee('Peter N., Turkana');
        $homeResponse->assertSee('Stories from homes and businesses already using the service.');
        $homeResponse->assertSee('stable connection');
    }

    public function test_storefront_uses_default_homepage_content_when_table_is_missing(): void
    {
        Schema::drop('homepage_contents');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Solar Flood Lights Kenya', false);
        $response->assertSee('Compare solar flood lights, solar street lights, motion sensor lights and installation accessories with current prices, stock availability and delivery options across Kenya.');
    }

    public function test_admin_homepage_update_shows_clear_error_when_table_is_missing(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0700000000',
        ]);

        Schema::drop('homepage_contents');

        $response = $this->actingAs($admin)->post('/admin/pages-content', [
            'hero_title' => 'Fallback title',
            'hero_description' => 'Fallback description that should not be saved because the table is missing.',
        ]);

        $response->assertRedirect('/admin/pages-content');
        $response->assertSessionHas('error', 'Homepage content storage is not ready yet. Run php artisan migrate to create the homepage_contents table.');
    }
}
