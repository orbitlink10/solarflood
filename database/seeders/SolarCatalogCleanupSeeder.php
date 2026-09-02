<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SolarCatalogCleanupSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const LEGACY_NON_SOLAR_CATEGORY_SLUGS = [
        'phones-tablets',
        'tvs-audio',
        'computing',
        'home-office',
        'beauty',
        'fashion',
        'appliances',
        'gaming',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $categoryIds = Category::query()
                ->whereIn('slug', self::LEGACY_NON_SOLAR_CATEGORY_SLUGS)
                ->pluck('id');

            if ($categoryIds->isEmpty()) {
                return;
            }

            Product::query()
                ->whereIn('category_id', $categoryIds)
                ->delete();

            Category::query()
                ->whereIn('id', $categoryIds)
                ->delete();
        });
    }
}
