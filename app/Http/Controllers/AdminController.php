<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomepageContent;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ProductContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    private function uniqueSlug(string $table, string $text, ?int $ignoreId = null): string
    {
        $base = Str::slug($text);
        if ($base === '') {
            $base = Str::lower(Str::random(8));
        }

        $slug = $base;
        $counter = 1;
        while (DB::table($table)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function nextSku(): string
    {
        $sku = strtoupper('SKU-'.Str::upper(Str::random(8)));

        while (Product::where('sku', $sku)->exists()) {
            $sku = strtoupper('SKU-'.Str::upper(Str::random(8)));
        }

        return $sku;
    }

    private function storeUploadedPublicFile(UploadedFile $file, string $directory): string
    {
        $targetDirectory = public_path(trim($directory, '/'));
        File::ensureDirectoryExists($targetDirectory);

        $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(10)).'.'.$file->getClientOriginalExtension();
        $file->move($targetDirectory, $filename);

        return '/'.trim($directory, '/').'/'.$filename;
    }

    private function deleteManagedUpload(?string $path, string $prefix): void
    {
        if (! $path) {
            return;
        }

        $normalizedPath = '/'.ltrim($path, '/');
        if (! Str::startsWith($normalizedPath, $prefix)) {
            return;
        }

        $absolutePath = public_path(ltrim($normalizedPath, '/'));
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private function normalizeHomepageText(?string $value, int $limit): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');

        return $text !== '' ? Str::limit($text, $limit, '') : null;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $items
     * @param  array<int, string>  $keys
     * @param  array<string, int>  $limits
     * @return array<int, array<string, string>>
     */
    private function normalizeHomepageItems(?array $items, array $keys, array $limits): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];
            foreach ($keys as $key) {
                $text = $this->normalizeHomepageText((string) ($item[$key] ?? ''), $limits[$key] ?? 255);
                if ($text === null) {
                    continue 2;
                }

                $row[$key] = $text;
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    private function cleanOptionalText(?string $value, int $limit): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');

        return $text !== '' ? Str::limit($text, $limit, '') : null;
    }

    private function cleanOptionalMultiline(?string $value, int $limit = 4000): ?string
    {
        $lines = preg_split('/\r\n|\r|\n/', strip_tags((string) $value)) ?: [];
        $text = implode(PHP_EOL, array_values(array_filter(array_map(
            fn (string $line): string => trim(preg_replace('/\s+/u', ' ', $line) ?? ''),
            $lines
        ))));

        return $text !== '' ? Str::limit($text, $limit, '') : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function seoRules(): array
    {
        return [
            'seo_title' => ['nullable', 'string', 'max:180'],
            'primary_keyword' => ['nullable', 'string', 'max:180'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow'])],
            'schema_type' => ['nullable', 'string', 'max:80'],
            'sitemap_enabled' => ['nullable', 'in:0,1'],
            'og_title' => ['nullable', 'string', 'max:180'],
            'og_description' => ['nullable', 'string', 'max:255'],
            'og_image' => ['nullable', 'url', 'max:255'],
            'faq_items' => ['nullable', 'array'],
            'faq_items.*.question' => ['nullable', 'string', 'max:220'],
            'faq_items.*.answer' => ['nullable', 'string', 'max:1200'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function seoPayload(array $data): array
    {
        return [
            'seo_title' => $this->cleanOptionalText($data['seo_title'] ?? null, 180),
            'primary_keyword' => $this->cleanOptionalText($data['primary_keyword'] ?? null, 180),
            'canonical_url' => $this->cleanOptionalText($data['canonical_url'] ?? null, 255),
            'robots' => $this->cleanOptionalText($data['robots'] ?? null, 40),
            'schema_type' => $this->cleanOptionalText($data['schema_type'] ?? null, 80),
            'sitemap_enabled' => $this->normalizeSitemapEnabled($data['sitemap_enabled'] ?? null),
            'og_title' => $this->cleanOptionalText($data['og_title'] ?? null, 180),
            'og_description' => ProductContent::sanitizeMetaDescription($data['og_description'] ?? null),
            'og_image' => $this->cleanOptionalText($data['og_image'] ?? null, 255),
            'faq_items' => $this->normalizeFaqItems($data['faq_items'] ?? null) ?: null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $items
     * @return array<int, array{question: string, answer: string}>
     */
    private function normalizeFaqItems(?array $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $question = $this->cleanOptionalText((string) ($item['question'] ?? ''), 220);
            $answer = $this->cleanOptionalText((string) ($item['answer'] ?? ''), 1200);

            if ($question && $answer) {
                $normalized[] = [
                    'question' => $question,
                    'answer' => $answer,
                ];
            }
        }

        return array_slice($normalized, 0, 12);
    }

    private function normalizeSitemapEnabled(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function validateTestimonialData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:180'],
            'role' => ['required', 'string', 'min:2', 'max:180'],
            'quote' => ['required', 'string', 'min:12', 'max:1200'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function syncPrimaryProductImage(Product $product, ?UploadedFile $image): void
    {
        if (! $image) {
            return;
        }

        $imagePath = $this->storeUploadedPublicFile($image, 'uploads/products');
        $primaryImage = $product->images()->orderByDesc('is_primary')->orderBy('sort_order')->first();

        if ($primaryImage) {
            $this->deleteManagedUpload($primaryImage->image_url, '/uploads/products/');
            $primaryImage->update([
                'image_url' => $imagePath,
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            return;
        }

        ProductImage::create([
            'product_id' => $product->id,
            'image_url' => $imagePath,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    private function resolveCategory(array $data): Category
    {
        if (! empty($data['subcategory_id'])) {
            return Category::query()->findOrFail($data['subcategory_id']);
        }

        if (! empty($data['category_id'])) {
            return Category::query()->findOrFail($data['category_id']);
        }

        $name = trim((string) ($data['category_name'] ?? ''));
        $existingCategory = Category::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if ($existingCategory) {
            return $existingCategory;
        }

        return Category::create([
            'name' => $name,
            'slug' => $this->uniqueSlug('categories', $name),
        ]);
    }

    private function adminVendor(Request $request, bool $create = false): ?Vendor
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $adminShopName = 'Mikrotik Kenya Store';

        $vendor = Vendor::query()->where('user_id', $user->id)->first();
        if ($vendor) {
            $updates = [];

            if (! $vendor->is_approved) {
                $updates['is_approved'] = true;
            }

            $legacyOfficial = 'official';
            if (in_array(strtolower((string) $vendor->shop_name), [
                'almar market '.$legacyOfficial.' store',
                'mikrotik kenya '.$legacyOfficial.' store',
            ], true)) {
                $updates['shop_name'] = $adminShopName;
                $updates['slug'] = $this->uniqueSlug('vendors', $adminShopName, $vendor->id);
            }

            if ($updates !== []) {
                $vendor->update($updates);
                $vendor->refresh();
            }

            return $vendor;
        }

        if (! $create) {
            return null;
        }

        return Vendor::create([
            'user_id' => $user->id,
            'shop_name' => $adminShopName,
            'slug' => $this->uniqueSlug('vendors', $adminShopName),
            'description' => 'Products managed by the marketplace admin.',
            'phone' => $user->phone ?: 'Admin desk',
            'address' => 'Platform managed catalog',
            'is_approved' => true,
        ]);
    }

    public function dashboard(Request $request): View
    {
        $adminVendor = $this->adminVendor($request);
        $stats = [
            'total_users' => User::count(),
            'new_users_30_days' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'active_users_24_hours' => DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subDay()->getTimestamp())
                ->distinct()
                ->count('user_id'),
            'total_vendors' => Vendor::count(),
            'pending_vendors' => Vendor::where('is_approved', false)->count(),
            'approved_vendors' => Vendor::where('is_approved', true)->count(),
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'recent_orders_7_days' => Order::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'gross_revenue' => (float) Order::sum('total_amount'),
        ];

        $recentOrders = Order::query()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        $pendingVendors = Vendor::query()
            ->with('user')
            ->where('is_approved', false)
            ->latest()
            ->limit(8)
            ->get();

        $adminProducts = $adminVendor
            ? $adminVendor->products()->with('category')->latest()->limit(12)->get()
            : collect();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'pendingVendors' => $pendingVendors,
            'adminProducts' => $adminProducts,
        ]);
    }

    public function pendingVendors(): View
    {
        return view('admin.vendors', [
            'vendors' => Vendor::query()
                ->with('user')
                ->where('is_approved', false)
                ->latest()
                ->get(),
        ]);
    }

    public function categoriesIndex(): View
    {
        return view('admin.categories_index', [
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->withCount('products')
                ->latest()
                ->get(),
        ]);
    }

    public function subcategoriesIndex(): View
    {
        return view('admin.subcategories_index', [
            'subcategories' => Category::query()
                ->whereNotNull('parent_id')
                ->with(['parent'])
                ->withCount('products')
                ->latest()
                ->get(),
        ]);
    }

    public function createCategoryForm(Request $request): View
    {
        $defaultParentId = $request->integer('parent_id');

        return view('admin.category_create', [
            'parents' => Category::query()
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(),
            'defaultParentId' => $defaultParentId > 0 ? $defaultParentId : null,
            'categoryContentFieldsReady' => Category::contentFieldsReady(),
            'categorySeoFieldsReady' => Category::seoFieldsReady(),
        ]);
    }

    public function editCategoryForm(Category $category): View
    {
        return view('admin.category_create', [
            'parents' => Category::query()
                ->whereNull('parent_id')
                ->whereKeyNot($category->id)
                ->orderBy('name')
                ->get(),
            'defaultParentId' => $category->parent_id,
            'categoryContentFieldsReady' => Category::contentFieldsReady(),
            'categorySeoFieldsReady' => Category::seoFieldsReady(),
            'categoryToEdit' => $category,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $categoryContentFieldsReady = Category::contentFieldsReady();
        $categorySeoFieldsReady = Category::seoFieldsReady();

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:120', 'unique:categories,name'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        if ($categoryContentFieldsReady) {
            $rules['meta_description'] = ['nullable', 'string', 'max:255'];
            $rules['description'] = ['nullable', 'string'];
        }

        if ($categorySeoFieldsReady) {
            $rules = array_merge($rules, $this->seoRules(), [
                'intro' => ['nullable', 'string', 'max:500'],
                'seo_content' => ['nullable', 'string'],
            ]);
        }

        $data = $request->validate($rules);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeUploadedPublicFile($request->file('image'), 'uploads/categories');
        }

        $payload = [
            'name' => $data['name'],
            'slug' => $this->uniqueSlug('categories', $data['name']),
            'parent_id' => $data['parent_id'] ?? null,
            'image_url' => $imagePath,
        ];

        if ($categoryContentFieldsReady) {
            $payload['meta_description'] = ProductContent::sanitizeMetaDescription($data['meta_description'] ?? null);
            $payload['description'] = ProductContent::sanitizeRichText($data['description'] ?? null);
        }

        if ($categorySeoFieldsReady) {
            $payload = array_merge($payload, $this->seoPayload($data), [
                'intro' => $this->cleanOptionalText($data['intro'] ?? null, 500),
                'seo_content' => ProductContent::sanitizeRichText($data['seo_content'] ?? null),
            ]);
        }

        $category = Category::create($payload);

        $redirectRoute = $category->parent_id ? 'admin.subcategories.index' : 'admin.categories.index';
        $message = $categoryContentFieldsReady
            ? 'Category saved successfully.'
            : 'Category saved. Run php artisan migrate to enable category meta description and description storage.';

        return redirect()->route($redirectRoute)->with('success', $message);
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $categoryContentFieldsReady = Category::contentFieldsReady();
        $categorySeoFieldsReady = Category::seoFieldsReady();

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn([$category->id])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        if ($categoryContentFieldsReady) {
            $rules['meta_description'] = ['nullable', 'string', 'max:255'];
            $rules['description'] = ['nullable', 'string'];
        }

        if ($categorySeoFieldsReady) {
            $rules = array_merge($rules, $this->seoRules(), [
                'intro' => ['nullable', 'string', 'max:500'],
                'seo_content' => ['nullable', 'string'],
            ]);
        }

        $data = $request->validate($rules);

        $imagePath = $category->image_url;
        if ($request->hasFile('image')) {
            $this->deleteManagedUpload($category->image_url, '/uploads/categories/');
            $imagePath = $this->storeUploadedPublicFile($request->file('image'), 'uploads/categories');
        }

        $payload = [
            'name' => $data['name'],
            'slug' => $category->slug ?: $this->uniqueSlug('categories', $data['name'], $category->id),
            'parent_id' => $data['parent_id'] ?? null,
            'image_url' => $imagePath,
        ];

        if ($categoryContentFieldsReady) {
            $payload['meta_description'] = ProductContent::sanitizeMetaDescription($data['meta_description'] ?? null);
            $payload['description'] = ProductContent::sanitizeRichText($data['description'] ?? null);
        }

        if ($categorySeoFieldsReady) {
            $payload = array_merge($payload, $this->seoPayload($data), [
                'intro' => $this->cleanOptionalText($data['intro'] ?? null, 500),
                'seo_content' => ProductContent::sanitizeRichText($data['seo_content'] ?? null),
            ]);
        }

        $category->update($payload);

        $redirectRoute = $category->parent_id ? 'admin.subcategories.index' : 'admin.categories.index';
        $message = $categoryContentFieldsReady
            ? 'Category updated successfully.'
            : 'Category updated. Run php artisan migrate to enable category meta description and description storage.';

        return redirect()->route($redirectRoute)->with('success', $message);
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Delete or move the sub categories in this category first.');
        }

        if ($category->products()->exists()) {
            return back()->with('error', 'Delete or move the products assigned to this category first.');
        }

        $this->deleteManagedUpload($category->image_url, '/uploads/categories/');
        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    public function productsIndex(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $products = Product::query()
            ->with([
                'category',
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products_index', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function ordersIndex(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $orders = Order::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('shipping_name', 'like', '%'.$search.'%')
                        ->orWhere('shipping_email', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders_index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'statuses' => ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'],
        ]);
    }

    public function pagesIndex(): View
    {
        $pagesStorageReady = Page::storageReady();

        return view('admin.pages_index', [
            'pages' => $pagesStorageReady
                ? Page::query()->latest()->paginate(20)
                : new LengthAwarePaginator([], 0, 20),
            'pagesStorageReady' => $pagesStorageReady,
        ]);
    }

    public function homepageContentForm(): View
    {
        return view('admin.homepage_content', [
            'homepageContent' => HomepageContent::current(),
            'homepageContentStorageReady' => HomepageContent::storageReady(),
            'products' => Product::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function testimonialsIndex(): View
    {
        $testimonialsStorageReady = Testimonial::storageReady();

        return view('admin.testimonials_index', [
            'testimonials' => $testimonialsStorageReady
                ? Testimonial::query()->ordered()->paginate(20)
                : new LengthAwarePaginator([], 0, 20),
            'testimonialsStorageReady' => $testimonialsStorageReady,
            'homepageContent' => HomepageContent::current(),
            'homepageContentStorageReady' => HomepageContent::storageReady(),
        ]);
    }

    public function updateTestimonialSettings(Request $request): RedirectResponse
    {
        if (! HomepageContent::storageReady()) {
            return redirect()
                ->route('admin.testimonials.index')
                ->with('error', 'Homepage content storage is not ready yet. Run php artisan migrate to create the homepage_contents table.');
        }

        $data = $request->validate([
            'testimonials_badge' => ['nullable', 'string', 'max:120'],
            'testimonials_title' => ['required', 'string', 'min:4', 'max:180'],
            'testimonials_intro' => ['nullable', 'string', 'max:500'],
        ]);

        $homepageContent = HomepageContent::query()->firstOrNew([
            'site_key' => HomepageContent::DEFAULT_SITE_KEY,
        ]);
        $baseline = HomepageContent::current();

        $homepageContent->hero_title = $homepageContent->hero_title ?: $baseline->hero_title;
        $homepageContent->hero_description = $homepageContent->hero_description ?: $baseline->hero_description;
        $homepageContent->testimonials_badge = $this->normalizeHomepageText($data['testimonials_badge'] ?? null, 120);
        $homepageContent->testimonials_title = $this->normalizeHomepageText($data['testimonials_title'] ?? null, 180)
            ?: $baseline->testimonialsTitle();
        $homepageContent->testimonials_intro = $this->normalizeHomepageText($data['testimonials_intro'] ?? null, 500);
        $homepageContent->save();

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial section settings updated successfully.');
    }

    public function updateHomepageContent(Request $request): RedirectResponse
    {
        if (! HomepageContent::storageReady()) {
            return redirect()
                ->route('admin.pages-content.edit')
                ->with('error', 'Homepage content storage is not ready yet. Run php artisan migrate to create the homepage_contents table.');
        }

        $data = $request->validate([
            'hero_title' => ['required', 'string', 'min:4', 'max:180'],
            'hero_description' => ['required', 'string', 'min:12', 'max:500'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_whatsapp' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'site_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'why_choose_title' => ['nullable', 'string', 'max:180'],
            'why_choose_intro' => ['nullable', 'string', 'max:500'],
            'why_choose_items' => ['nullable', 'array'],
            'why_choose_items.*.title' => ['nullable', 'string', 'max:180'],
            'why_choose_items.*.description' => ['nullable', 'string', 'max:255'],
            'testimonials_badge' => ['nullable', 'string', 'max:120'],
            'testimonials_title' => ['nullable', 'string', 'max:180'],
            'testimonials_intro' => ['nullable', 'string', 'max:500'],
            'testimonial_items' => ['nullable', 'array'],
            'testimonial_items.*.quote' => ['nullable', 'string', 'max:1200'],
            'testimonial_items.*.name' => ['nullable', 'string', 'max:180'],
            'testimonial_items.*.role' => ['nullable', 'string', 'max:180'],
            'faq_badge' => ['nullable', 'string', 'max:120'],
            'faq_title' => ['nullable', 'string', 'max:180'],
            'faq_intro' => ['nullable', 'string', 'max:500'],
            'faq_items' => ['nullable', 'array'],
            'faq_items.*.question' => ['nullable', 'string', 'max:220'],
            'faq_items.*.answer' => ['nullable', 'string', 'max:1200'],
            'content_body' => ['nullable', 'string'],
        ]);

        $homepageContent = HomepageContent::query()->firstOrNew([
            'site_key' => HomepageContent::DEFAULT_SITE_KEY,
        ]);
        $baseline = $homepageContent->exists ? $homepageContent : HomepageContent::current();

        $homepageContent->hero_title = $data['hero_title'];
        $homepageContent->hero_description = $data['hero_description'];
        $homepageContent->contact_phone = $request->has('contact_phone')
            ? $this->normalizeHomepageText($data['contact_phone'] ?? null, 40)
            : $baseline->contactPhone();
        $homepageContent->contact_whatsapp = $request->has('contact_whatsapp')
            ? $this->normalizeHomepageText($data['contact_whatsapp'] ?? null, 40)
            : $baseline->contactWhatsApp();
        $homepageContent->contact_email = $request->has('contact_email')
            ? $this->normalizeHomepageText($data['contact_email'] ?? null, 190)
            : $baseline->contactEmail();
        $homepageContent->why_choose_title = $request->has('why_choose_title')
            ? $this->normalizeHomepageText($data['why_choose_title'] ?? null, 180)
            : $baseline->whyChooseTitle();
        $homepageContent->why_choose_intro = $request->has('why_choose_intro')
            ? $this->normalizeHomepageText($data['why_choose_intro'] ?? null, 500)
            : $baseline->whyChooseIntro();
        $homepageContent->why_choose_items = $request->has('why_choose_items')
            ? ($this->normalizeHomepageItems($data['why_choose_items'] ?? null, ['title', 'description'], ['title' => 180, 'description' => 255]) ?: $baseline->whyChooseItems())
            : $baseline->whyChooseItems();
        $homepageContent->testimonials_badge = $request->has('testimonials_badge')
            ? $this->normalizeHomepageText($data['testimonials_badge'] ?? null, 120)
            : $baseline->testimonialsBadge();
        $homepageContent->testimonials_title = $request->has('testimonials_title')
            ? $this->normalizeHomepageText($data['testimonials_title'] ?? null, 180)
            : $baseline->testimonialsTitle();
        $homepageContent->testimonials_intro = $request->has('testimonials_intro')
            ? $this->normalizeHomepageText($data['testimonials_intro'] ?? null, 500)
            : $baseline->testimonialsIntro();
        $homepageContent->testimonial_items = $request->has('testimonial_items')
            ? ($this->normalizeHomepageItems($data['testimonial_items'] ?? null, ['quote', 'name', 'role'], ['quote' => 1200, 'name' => 180, 'role' => 180]) ?: $baseline->testimonialItems())
            : $baseline->testimonialItems();
        $homepageContent->faq_badge = $request->has('faq_badge')
            ? $this->normalizeHomepageText($data['faq_badge'] ?? null, 120)
            : $baseline->faqBadge();
        $homepageContent->faq_title = $request->has('faq_title')
            ? $this->normalizeHomepageText($data['faq_title'] ?? null, 180)
            : $baseline->faqTitle();
        $homepageContent->faq_intro = $request->has('faq_intro')
            ? $this->normalizeHomepageText($data['faq_intro'] ?? null, 500)
            : $baseline->faqIntro();
        $homepageContent->faq_items = $request->has('faq_items')
            ? ($this->normalizeHomepageItems($data['faq_items'] ?? null, ['question', 'answer'], ['question' => 220, 'answer' => 1200]) ?: $baseline->faqItems())
            : $baseline->faqItems();
        $normalizedContentBody = ProductContent::sanitizeRichText($data['content_body'] ?? null);
        $homepageContent->content_body = $request->has('content_body')
            ? ($normalizedContentBody !== '' ? $normalizedContentBody : $baseline->contentBody())
            : $baseline->contentBody();
        $homepageContent->featured_product_ids = $baseline->featuredProductIds();

        if ($request->hasFile('site_logo')) {
            $directory = public_path('uploads/homepage-content');
            File::ensureDirectoryExists($directory);

            if ($homepageContent->site_logo_path && File::exists(public_path($homepageContent->site_logo_path))) {
                File::delete(public_path($homepageContent->site_logo_path));
            }

            $logo = $request->file('site_logo');
            $filename = now()->format('YmdHis').'-logo-'.Str::lower(Str::random(10)).'.'.$logo->getClientOriginalExtension();
            $logo->move($directory, $filename);

            $homepageContent->site_logo_path = 'uploads/homepage-content/'.$filename;
        }

        if ($request->hasFile('hero_image')) {
            $directory = public_path('uploads/homepage-content');
            File::ensureDirectoryExists($directory);

            if ($homepageContent->hero_image_path && File::exists(public_path($homepageContent->hero_image_path))) {
                File::delete(public_path($homepageContent->hero_image_path));
            }

            $image = $request->file('hero_image');
            $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(10)).'.'.$image->getClientOriginalExtension();
            $image->move($directory, $filename);

            $homepageContent->hero_image_path = 'uploads/homepage-content/'.$filename;
        }

        $homepageContent->save();

        return redirect()->route('admin.pages-content.edit')->with('success', 'Homepage content updated successfully.');
    }

    public function updateFeaturedProducts(Request $request): RedirectResponse
    {
        if (! HomepageContent::storageReady()) {
            return redirect()
                ->route('admin.pages-content.edit')
                ->with('error', 'Homepage content storage is not ready yet. Run php artisan migrate to create the homepage_contents table.');
        }

        $request->merge([
            'featured_product_ids' => $this->normalizeFeaturedProductIds($request),
        ]);

        $data = $request->validate([
            'featured_product_ids' => ['nullable', 'array', 'max:6'],
            'featured_product_ids.*' => ['required', 'integer', 'exists:products,id'],
        ]);

        $homepageContent = HomepageContent::query()->firstOrNew([
            'site_key' => HomepageContent::DEFAULT_SITE_KEY,
        ]);
        $baseline = $homepageContent->exists ? $homepageContent : HomepageContent::current();

        $homepageContent->hero_title = $homepageContent->hero_title ?: $baseline->hero_title;
        $homepageContent->hero_description = $homepageContent->hero_description ?: $baseline->hero_description;
        $homepageContent->featured_product_ids = $data['featured_product_ids'] ?? [];
        $homepageContent->save();

        return redirect()
            ->route('admin.pages-content.edit')
            ->with('success', 'Featured homepage products updated successfully.');
    }

    /**
     * @return array<int>
     */
    private function normalizeFeaturedProductIds(Request $request): array
    {
        return collect((array) $request->input('featured_product_ids'))
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take(6)
            ->values()
            ->all();
    }

    public function createTestimonialForm(): View
    {
        return view('admin.testimonial_create', [
            'testimonialsStorageReady' => Testimonial::storageReady(),
            'testimonialToEdit' => null,
        ]);
    }

    public function storeTestimonial(Request $request): RedirectResponse
    {
        if (! Testimonial::storageReady()) {
            return redirect()
                ->route('admin.testimonials.index')
                ->with('error', 'Testimonial storage is not ready yet. Run php artisan migrate to create the testimonials table.');
        }

        $data = $this->validateTestimonialData($request);

        Testimonial::create([
            'name' => trim($data['name']),
            'role' => trim($data['role']),
            'quote' => trim($data['quote']),
            'rating' => (int) $data['rating'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial added successfully.');
    }

    public function showTestimonial(Testimonial $testimonial): View
    {
        return view('admin.testimonial_show', [
            'testimonial' => $testimonial,
        ]);
    }

    public function editTestimonialForm(Testimonial $testimonial): View
    {
        return view('admin.testimonial_create', [
            'testimonialsStorageReady' => Testimonial::storageReady(),
            'testimonialToEdit' => $testimonial,
        ]);
    }

    public function updateTestimonial(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $this->validateTestimonialData($request);

        $testimonial->update([
            'name' => trim($data['name']),
            'role' => trim($data['role']),
            'quote' => trim($data['quote']),
            'rating' => (int) $data['rating'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial updated successfully.');
    }

    public function destroyTestimonial(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back()->with('success', 'Testimonial deleted successfully.');
    }

    public function createPageForm(): View
    {
        return view('admin.page_create', [
            'pagesStorageReady' => Page::storageReady(),
            'pageToEdit' => null,
        ]);
    }

    private function validatePageData(Request $request, ?Page $page = null): array
    {
        $rules = [
            'meta_title' => ['required', 'string', 'min:2', 'max:180'],
            'meta_description' => ['required', 'string', 'min:10', 'max:255'],
            'title' => ['required', 'string', 'min:2', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'image_url' => ['nullable', 'url', 'max:255'],
            'alt_text' => ['nullable', 'string', 'min:2', 'max:255', 'required_with:image_url'],
            'heading_two' => ['required', 'string', 'min:2', 'max:180'],
            'type' => ['required', 'in:page,post'],
            'body' => ['required', 'string'],
        ];

        if (Page::seoFieldsReady()) {
            $rules = array_merge($rules, $this->seoRules());
        }

        return $request->validate($rules);
    }

    private function persistPage(Page $page, array $data): void
    {
        $payload = [
            'meta_title' => Str::limit(trim(strip_tags($data['meta_title'])), 180, ''),
            'meta_description' => ProductContent::sanitizeMetaDescription($data['meta_description']),
            'title' => trim($data['title']),
            'heading_two' => Str::limit(trim(strip_tags($data['heading_two'])), 180, ''),
            'slug' => ! empty($data['slug']) ? Str::slug($data['slug']) : $this->uniqueSlug('pages', $data['title'], $page->id),
            'image_url' => $data['image_url'] ?? null,
            'alt_text' => ! empty($data['alt_text']) ? trim($data['alt_text']) : null,
            'type' => $data['type'],
            'body' => ProductContent::sanitizeRichText($data['body']),
        ];

        if (Page::seoFieldsReady()) {
            $payload = array_merge($payload, $this->seoPayload($data));
        }

        $page->fill($payload);

        $page->save();
    }

    public function editPageForm(Page $page): View
    {
        return view('admin.page_create', [
            'pagesStorageReady' => Page::storageReady(),
            'pageToEdit' => $page,
        ]);
    }

    public function storePage(Request $request): RedirectResponse
    {
        if (! Page::storageReady()) {
            return redirect()
                ->route('admin.pages.index')
                ->with('error', 'Page storage is not ready yet. Run php artisan migrate to create the pages table.');
        }

        $data = $this->validatePageData($request);

        $this->persistPage(new Page, $data);

        return redirect()->route('admin.pages.index')->with('success', 'Page saved successfully.');
    }

    public function updatePage(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validatePageData($request, $page);

        $this->persistPage($page, $data);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully.');
    }

    public function destroyPage(Page $page): RedirectResponse
    {
        $page->delete();

        return back()->with('success', 'Page deleted successfully.');
    }

    public function invoicesIndex(): View
    {
        return view('admin.invoices_index', [
            'orders' => Order::query()
                ->with('user')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function createProductForm(): View
    {
        return view('admin.product_create', [
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->with(['children' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function editProductForm(Product $product): View
    {
        $product->load(['category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')]);

        return view('admin.product_create', [
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->with(['children' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'productToEdit' => $product,
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:180'],
            'category_id' => ['nullable', 'exists:categories,id', 'required_without:category_name'],
            'category_name' => ['nullable', 'string', 'min:2', 'max:120', 'required_without:category_id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'compare_at_price' => ['nullable', 'numeric', 'gte:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        if (Product::seoFieldsReady()) {
            $rules = array_merge($rules, $this->seoRules(), [
                'model_number' => ['nullable', 'string', 'max:120'],
                'brand' => ['nullable', 'string', 'max:120'],
                'key_use' => ['nullable', 'string', 'max:255'],
                'key_specifications' => ['nullable', 'string'],
                'use_cases' => ['nullable', 'string'],
                'technical_specifications' => ['nullable', 'string'],
                'whats_in_box' => ['nullable', 'string'],
                'recommended_applications' => ['nullable', 'string'],
                'choose_another_model' => ['nullable', 'string', 'max:1200'],
                'compatibility' => ['nullable', 'string', 'max:1200'],
                'power_requirements' => ['nullable', 'string', 'max:1200'],
                'warranty_info' => ['nullable', 'string', 'max:1200'],
                'delivery_info' => ['nullable', 'string', 'max:1200'],
                'payment_info' => ['nullable', 'string', 'max:1200'],
            ]);
        }

        if (Product::officialMediaFieldsReady()) {
            $rules = array_merge($rules, [
                'official_image_url' => ['nullable', 'url', 'max:500'],
                'official_video_url' => ['nullable', 'url', 'max:500'],
            ]);
        }

        $data = $request->validate($rules);

        if (! empty($data['subcategory_id'])) {
            $subcategory = Category::query()
                ->whereKey($data['subcategory_id'])
                ->whereNotNull('parent_id')
                ->first();

            if (! $subcategory) {
                throw ValidationException::withMessages([
                    'subcategory_id' => 'Select a valid subcategory.',
                ]);
            }

            if (! empty($data['category_id']) && $subcategory->parent_id !== (int) $data['category_id']) {
                throw ValidationException::withMessages([
                    'subcategory_id' => 'Select a subcategory that belongs to the chosen category.',
                ]);
            }
        }

        $vendor = $this->adminVendor($request, true);
        if (! $vendor) {
            return redirect()->route('admin.dashboard')->with('error', 'Unable to initialize the admin store.');
        }

        $category = $this->resolveCategory($data);

        $payload = [
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug('products', $data['name']),
            'description' => ProductContent::sanitizeRichText($data['description'] ?? null),
            'meta_description' => ProductContent::sanitizeMetaDescription($data['meta_description'] ?? null),
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'stock' => $data['stock'],
            'sku' => $this->nextSku(),
            'status' => 'active',
        ];

        if (Product::seoFieldsReady()) {
            $payload = array_merge($payload, $this->seoPayload($data), [
                'model_number' => $this->cleanOptionalText($data['model_number'] ?? null, 120),
                'brand' => $this->cleanOptionalText($data['brand'] ?? null, 120),
                'key_use' => $this->cleanOptionalText($data['key_use'] ?? null, 255),
                'key_specifications' => $this->cleanOptionalMultiline($data['key_specifications'] ?? null),
                'use_cases' => $this->cleanOptionalMultiline($data['use_cases'] ?? null),
                'technical_specifications' => $this->cleanOptionalMultiline($data['technical_specifications'] ?? null),
                'whats_in_box' => $this->cleanOptionalMultiline($data['whats_in_box'] ?? null),
                'recommended_applications' => $this->cleanOptionalMultiline($data['recommended_applications'] ?? null),
                'choose_another_model' => $this->cleanOptionalText($data['choose_another_model'] ?? null, 1200),
                'compatibility' => $this->cleanOptionalText($data['compatibility'] ?? null, 1200),
                'power_requirements' => $this->cleanOptionalText($data['power_requirements'] ?? null, 1200),
                'warranty_info' => $this->cleanOptionalText($data['warranty_info'] ?? null, 1200),
                'delivery_info' => $this->cleanOptionalText($data['delivery_info'] ?? null, 1200),
                'payment_info' => $this->cleanOptionalText($data['payment_info'] ?? null, 1200),
            ]);
        }

        if (Product::officialMediaFieldsReady()) {
            $payload = array_merge($payload, [
                'official_image_url' => $this->cleanOptionalText($data['official_image_url'] ?? null, 500),
                'official_video_url' => $this->cleanOptionalText($data['official_video_url'] ?? null, 500),
            ]);
        }

        $product = Product::create($payload);

        $this->syncPrimaryProductImage($product, $request->file('image'));

        return redirect()->route('admin.products.index')->with('success', 'Product added to the admin catalog.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:180'],
            'category_id' => ['nullable', 'exists:categories,id', 'required_without:category_name'],
            'category_name' => ['nullable', 'string', 'min:2', 'max:120', 'required_without:category_id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'compare_at_price' => ['nullable', 'numeric', 'gte:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        if (Product::seoFieldsReady()) {
            $rules = array_merge($rules, $this->seoRules(), [
                'model_number' => ['nullable', 'string', 'max:120'],
                'brand' => ['nullable', 'string', 'max:120'],
                'key_use' => ['nullable', 'string', 'max:255'],
                'key_specifications' => ['nullable', 'string'],
                'use_cases' => ['nullable', 'string'],
                'technical_specifications' => ['nullable', 'string'],
                'whats_in_box' => ['nullable', 'string'],
                'recommended_applications' => ['nullable', 'string'],
                'choose_another_model' => ['nullable', 'string', 'max:1200'],
                'compatibility' => ['nullable', 'string', 'max:1200'],
                'power_requirements' => ['nullable', 'string', 'max:1200'],
                'warranty_info' => ['nullable', 'string', 'max:1200'],
                'delivery_info' => ['nullable', 'string', 'max:1200'],
                'payment_info' => ['nullable', 'string', 'max:1200'],
            ]);
        }

        if (Product::officialMediaFieldsReady()) {
            $rules = array_merge($rules, [
                'official_image_url' => ['nullable', 'url', 'max:500'],
                'official_video_url' => ['nullable', 'url', 'max:500'],
            ]);
        }

        $data = $request->validate($rules);

        if (! empty($data['subcategory_id'])) {
            $subcategory = Category::query()
                ->whereKey($data['subcategory_id'])
                ->whereNotNull('parent_id')
                ->first();

            if (! $subcategory) {
                throw ValidationException::withMessages([
                    'subcategory_id' => 'Select a valid subcategory.',
                ]);
            }

            if (! empty($data['category_id']) && $subcategory->parent_id !== (int) $data['category_id']) {
                throw ValidationException::withMessages([
                    'subcategory_id' => 'Select a subcategory that belongs to the chosen category.',
                ]);
            }
        }

        $category = $this->resolveCategory($data);

        $payload = [
            'category_id' => $category->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug('products', $data['name'], $product->id),
            'description' => ProductContent::sanitizeRichText($data['description'] ?? null),
            'meta_description' => ProductContent::sanitizeMetaDescription($data['meta_description'] ?? null),
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'stock' => $data['stock'],
        ];

        if (Product::seoFieldsReady()) {
            $payload = array_merge($payload, $this->seoPayload($data), [
                'model_number' => $this->cleanOptionalText($data['model_number'] ?? null, 120),
                'brand' => $this->cleanOptionalText($data['brand'] ?? null, 120),
                'key_use' => $this->cleanOptionalText($data['key_use'] ?? null, 255),
                'key_specifications' => $this->cleanOptionalMultiline($data['key_specifications'] ?? null),
                'use_cases' => $this->cleanOptionalMultiline($data['use_cases'] ?? null),
                'technical_specifications' => $this->cleanOptionalMultiline($data['technical_specifications'] ?? null),
                'whats_in_box' => $this->cleanOptionalMultiline($data['whats_in_box'] ?? null),
                'recommended_applications' => $this->cleanOptionalMultiline($data['recommended_applications'] ?? null),
                'choose_another_model' => $this->cleanOptionalText($data['choose_another_model'] ?? null, 1200),
                'compatibility' => $this->cleanOptionalText($data['compatibility'] ?? null, 1200),
                'power_requirements' => $this->cleanOptionalText($data['power_requirements'] ?? null, 1200),
                'warranty_info' => $this->cleanOptionalText($data['warranty_info'] ?? null, 1200),
                'delivery_info' => $this->cleanOptionalText($data['delivery_info'] ?? null, 1200),
                'payment_info' => $this->cleanOptionalText($data['payment_info'] ?? null, 1200),
            ]);
        }

        if (Product::officialMediaFieldsReady()) {
            $payload = array_merge($payload, [
                'official_image_url' => $this->cleanOptionalText($data['official_image_url'] ?? null, 500),
                'official_video_url' => $this->cleanOptionalText($data['official_video_url'] ?? null, 500),
            ]);
        }

        $product->update($payload);

        $this->syncPrimaryProductImage($product, $request->file('image'));

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        $product->load('images');

        foreach ($product->images as $image) {
            $this->deleteManagedUpload($image->image_url, '/uploads/products/');
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    public function approveVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update(['is_approved' => true]);
        $vendor->products()->where('status', 'draft')->update(['status' => 'active']);
        $vendor->user()->update(['role' => 'vendor']);

        return back()->with('success', 'Vendor approved successfully.');
    }
}
