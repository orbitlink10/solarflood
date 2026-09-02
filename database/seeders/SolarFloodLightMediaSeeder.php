<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\SolarFloodLightMediaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SolarFloodLightMediaSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const PLACEHOLDER_IMAGES = [
        'assets/product-placeholder.svg',
        '/assets/product-placeholder.svg',
    ];

    public function run(): void
    {
        $this->syncCategoryImages();
        $this->syncProductImages();
    }

    private function syncCategoryImages(): void
    {
        foreach (SolarFloodLightMediaCatalog::categoryImages() as $slug => $imageUrl) {
            $category = Category::query()->where('slug', $slug)->first();

            if (! $category) {
                continue;
            }

            $updates = [];

            if ($this->missingUrl($category->image_url)) {
                $updates['image_url'] = $imageUrl;
            }

            if (Schema::hasColumn('categories', 'og_image') && $this->missingUrl($category->og_image)) {
                $updates['og_image'] = $imageUrl;
            }

            if ($updates !== []) {
                $category->update($updates);
            }
        }
    }

    private function syncProductImages(): void
    {
        foreach (SolarFloodLightMediaCatalog::productImagesBySku() as $sku => $imageUrl) {
            $product = Product::query()->where('sku', $sku)->first();

            if (! $product) {
                continue;
            }

            $updates = [];

            if (Schema::hasColumn('products', 'official_image_url') && $this->missingUrl($product->official_image_url)) {
                $updates['official_image_url'] = $imageUrl;
            }

            if (Schema::hasColumn('products', 'official_gallery_images') && empty($product->official_gallery_images)) {
                $updates['official_gallery_images'] = [$imageUrl];
            }

            if (Schema::hasColumn('products', 'og_image') && $this->missingUrl($product->og_image)) {
                $updates['og_image'] = $imageUrl;
            }

            if ($updates !== []) {
                $product->update($updates);
            }

            $product->load(['images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')]);

            /** @var ProductImage|null $primaryImage */
            $primaryImage = $product->images->firstWhere('is_primary', true) ?: $product->images->first();

            if (! $this->missingProductImage($primaryImage)) {
                continue;
            }

            if ($primaryImage) {
                $primaryImage->update([
                    'image_url' => $imageUrl,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);

                $product->images()
                    ->whereKeyNot($primaryImage->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);

                continue;
            }

            ProductImage::query()->create([
                'product_id' => $product->id,
                'image_url' => $imageUrl,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }
    }

    private function missingProductImage(?ProductImage $image): bool
    {
        if (! $image) {
            return true;
        }

        if ($this->missingUrl($image->image_url)) {
            return true;
        }

        return $image->publicUrl() === null;
    }

    private function missingUrl(?string $url): bool
    {
        $url = trim((string) $url);

        return $url === '' || in_array($url, self::PLACEHOLDER_IMAGES, true);
    }
}
