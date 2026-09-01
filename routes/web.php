<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\VendorController;
use App\Support\SolarFloodLightSeoCatalog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/sitemap_index.xml', SitemapController::class)->name('sitemap.index');
Route::get('/compare/{comparison}', [ComparisonController::class, 'show'])
    ->where('comparison', implode('|', array_keys(SolarFloodLightSeoCatalog::comparisonPages())))
    ->name('comparison.show');
Route::get('/category/{category}', [StorefrontController::class, 'showCategory'])->name('category.show');
Route::get('/product/{product}', [StorefrontController::class, 'show'])->name('product.show');
Route::get('/products/{product}', [StorefrontController::class, 'redirectLegacyProduct']);
Route::get('/categories/{category}', [StorefrontController::class, 'redirectLegacyCategory']);
Route::get('/pages/{page}', [StorefrontController::class, 'redirectLegacyPage']);
Route::get('/{categorySlug}', [StorefrontController::class, 'redirectTopLevelCategory'])
    ->where('categorySlug', implode('|', array_keys(SolarFloodLightSeoCatalog::topLevelCategoryRedirects())));
Route::get('/uploads/products/{filename}', function (string $filename) {
    $paths = [
        public_path('uploads/products/'.$filename),
        storage_path('app/public/uploads/products/'.$filename),
        storage_path('app/uploads/products/'.$filename),
    ];

    foreach ($paths as $path) {
        if (File::isFile($path)) {
            return response()->file($path, [
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }
    }

    abort(404);
})->where('filename', '[A-Za-z0-9._-]+')->name('uploads.products.show');

Route::redirect('/login', '/login.php');
Route::redirect('/admin/register', '/admin/register.php');

Route::middleware('guest')->group(function (): void {
    Route::get('/login.php', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login.php', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::get('/admin/register.php', [AuthController::class, 'showAdminRegister'])->name('admin.register');
    Route::post('/admin/register.php', [AuthController::class, 'registerAdmin'])->name('admin.register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product:slug}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/items/{cartItem}/update', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/items/{cartItem}/remove', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/checkout', [CartController::class, 'checkoutForm'])->name('checkout.form');
    Route::post('/checkout', [CartController::class, 'checkout'])->name('checkout.submit');
    Route::get('/checkout/success/{orderNumber}', [CartController::class, 'success'])->name('checkout.success');

    Route::get('/become-vendor', [VendorController::class, 'applyForm'])->name('vendor.apply.form');
    Route::post('/become-vendor', [VendorController::class, 'apply'])->name('vendor.apply.submit');
});

Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function (): void {
    Route::get('/dashboard', [VendorController::class, 'dashboard'])->name('dashboard');
    Route::get('/products/create', [VendorController::class, 'createProductForm'])->name('products.create');
    Route::post('/products', [VendorController::class, 'storeProduct'])->name('products.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/pages-content', [AdminController::class, 'homepageContentForm'])->name('pages-content.edit');
    Route::post('/pages-content', [AdminController::class, 'updateHomepageContent'])->name('pages-content.update');
    Route::post('/featured-products', [AdminController::class, 'updateFeaturedProducts'])->name('featured-products.update');
    Route::get('/testimonials', [AdminController::class, 'testimonialsIndex'])->name('testimonials.index');
    Route::post('/testimonials/settings', [AdminController::class, 'updateTestimonialSettings'])->name('testimonials.settings.update');
    Route::get('/testimonials/create', [AdminController::class, 'createTestimonialForm'])->name('testimonials.create');
    Route::post('/testimonials', [AdminController::class, 'storeTestimonial'])->name('testimonials.store');
    Route::get('/testimonials/{testimonial}', [AdminController::class, 'showTestimonial'])->name('testimonials.show');
    Route::get('/testimonials/{testimonial}/edit', [AdminController::class, 'editTestimonialForm'])->name('testimonials.edit');
    Route::put('/testimonials/{testimonial}', [AdminController::class, 'updateTestimonial'])->name('testimonials.update');
    Route::delete('/testimonials/{testimonial}', [AdminController::class, 'destroyTestimonial'])->name('testimonials.destroy');
    Route::get('/categories', [AdminController::class, 'categoriesIndex'])->name('categories.index');
    Route::get('/categories/create', [AdminController::class, 'createCategoryForm'])->name('categories.create');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminController::class, 'editCategoryForm'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
    Route::get('/sub-categories', [AdminController::class, 'subcategoriesIndex'])->name('subcategories.index');
    Route::get('/products', [AdminController::class, 'productsIndex'])->name('products.index');
    Route::get('/products/create', [AdminController::class, 'createProductForm'])->name('products.create');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminController::class, 'editProductForm'])->name('products.edit');
    Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{product}', [AdminController::class, 'destroyProduct'])->name('products.destroy');
    Route::get('/pages', [AdminController::class, 'pagesIndex'])->name('pages.index');
    Route::get('/pages/create', [AdminController::class, 'createPageForm'])->name('pages.create');
    Route::post('/pages', [AdminController::class, 'storePage'])->name('pages.store');
    Route::get('/pages/{page}/edit', [AdminController::class, 'editPageForm'])->name('pages.edit');
    Route::put('/pages/{page}', [AdminController::class, 'updatePage'])->name('pages.update');
    Route::delete('/pages/{page}', [AdminController::class, 'destroyPage'])->name('pages.destroy');
    Route::get('/orders', [AdminController::class, 'ordersIndex'])->name('orders.index');
    Route::get('/invoices', [AdminController::class, 'invoicesIndex'])->name('invoices.index');
    Route::get('/vendors', [AdminController::class, 'pendingVendors'])->name('vendors.pending');
    Route::post('/vendors/{vendor}/approve', [AdminController::class, 'approveVendor'])->name('vendors.approve');
});

Route::get('/{page}', [StorefrontController::class, 'showPage'])
    ->where('page', '[A-Za-z0-9-]+')
    ->name('pages.show');
