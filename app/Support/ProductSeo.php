<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductSeo
{
    public static function brand(Product $product): string
    {
        return self::columnValue($product, 'brand')
            ?: config('app.name', 'Solar Flood Lights Kenya');
    }

    public static function isSolarLightingProduct(Product $product): bool
    {
        return SolarFloodLightSeoCatalog::productIntentSlug($product) !== null;
    }

    public static function displayName(Product $product): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $product->name) ?? $product->name);
        $name = trim(preg_replace('/\s*[-\x{2013}\x{2014}]\s*$/u', '', $name)) ?? $name;

        return $name !== '' ? $name : self::model($product);
    }

    public static function model(Product $product): string
    {
        if ($model = self::columnValue($product, 'model_number')) {
            return trim(preg_replace('/\s*[-\x{2013}\x{2014}]\s*$/u', '', $model) ?? $model);
        }

        $name = trim(preg_replace('/\s+/u', ' ', $product->name) ?? $product->name);
        $model = trim(preg_replace('/\s*[-\x{2013}\x{2014}]\s*$/u', '', $name)) ?? $name;

        return trim($model) !== '' ? trim($model) : $product->sku;
    }

    public static function typeLabel(Product $product): string
    {
        return match (SolarFloodLightSeoCatalog::productIntentSlug($product)) {
            SolarFloodLightSeoCatalog::PRICE_AUTHORITY_SLUG => 'Solar Flood Light',
            'solar-outdoor-lights' => 'Outdoor Solar Light',
            'solar-motion-sensor-lights' => 'Motion Sensor Solar Light',
            'solar-street-lights' => 'Solar Street Light',
            'solar-security-lights' => 'Solar Security Light',
            default => 'Solar Lighting',
        };
    }

    public static function keyUse(Product $product): string
    {
        if ($keyUse = self::columnValue($product, 'key_use')) {
            return $keyUse;
        }

        return match (SolarFloodLightSeoCatalog::productIntentSlug($product)) {
            'solar-outdoor-lights' => 'Outdoor compound, yard and parking-area lighting',
            'solar-motion-sensor-lights' => 'Automatic security lighting for gates and walkways',
            'solar-street-lights' => 'Road, estate, school and public-area lighting',
            'solar-security-lights' => 'Perimeter, CCTV-zone and commercial security lighting',
            default => 'Solar-powered outdoor flood lighting',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function specs(Product $product): array
    {
        $specs = [
            'Model' => self::model($product),
            'Brand' => self::brand($product),
            'SKU' => $product->sku,
            'Category' => $product->category?->name ?? 'Solar lighting products',
            'Current price' => 'KSh '.number_format((float) $product->price, 2),
            'Availability' => $product->stock > 0 ? 'In stock' : 'Out of stock',
        ];

        foreach (self::linesFromColumn($product, 'technical_specifications') as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = array_map('trim', explode(':', $line, 2));
                if ($key !== '' && $value !== '') {
                    $specs[$key] = $value;
                }
            }
        }

        return array_filter($specs, fn (?string $value): bool => trim((string) $value) !== '');
    }

    /**
     * @return array<int, string>
     */
    public static function useCases(Product $product): array
    {
        $custom = self::linesFromColumn($product, 'use_cases');
        if ($custom !== []) {
            return $custom;
        }

        return match (SolarFloodLightSeoCatalog::productIntentSlug($product)) {
            'solar-outdoor-lights' => ['Home compounds and yards', 'Parking areas and shop fronts', 'Farm sheds and perimeter spaces'],
            'solar-motion-sensor-lights' => ['Gate and doorway security', 'Walkways and stair areas', 'Energy-saving night lighting'],
            'solar-street-lights' => ['Estate roads and driveways', 'Schools, churches and public spaces', 'Parking yards and access roads'],
            'solar-security-lights' => ['Perimeter walls', 'CCTV blind spots', 'Commercial yards and loading areas'],
            default => ['Compound lighting', 'Outdoor security lighting', 'Off-grid lighting where grid power is unreliable'],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function applications(Product $product): array
    {
        return self::linesFromColumn($product, 'recommended_applications') ?: self::useCases($product);
    }

    public static function compatibility(Product $product): string
    {
        return self::columnValue($product, 'compatibility')
            ?: 'Suitable for outdoor solar lighting installations when the mounting height, panel exposure, battery capacity and weather rating match the site conditions.';
    }

    public static function powerRequirements(Product $product): string
    {
        return self::columnValue($product, 'power_requirements')
            ?: 'Confirm solar panel wattage, battery capacity, charging time and expected lighting hours before purchase, especially for security or commercial lighting.';
    }

    public static function warrantyInfo(Product $product): string
    {
        return self::columnValue($product, 'warranty_info')
            ?: 'Warranty terms depend on the seller, battery type and product condition. Confirm warranty coverage before checkout or quotation approval.';
    }

    public static function deliveryInfo(Product $product): string
    {
        return self::columnValue($product, 'delivery_info')
            ?: 'Delivery options and timelines are confirmed during checkout or direct enquiry based on stock location, order size and destination in Kenya.';
    }

    public static function paymentInfo(Product $product): string
    {
        return self::columnValue($product, 'payment_info')
            ?: 'Payment options are confirmed at checkout or through the seller before dispatch.';
    }

    public static function chooseAnotherModel(Product $product): ?string
    {
        return self::columnValue($product, 'choose_another_model');
    }

    /**
     * @return array<int, string>
     */
    public static function whatsInBox(Product $product): array
    {
        return self::linesFromColumn($product, 'whats_in_box')
            ?: [self::displayName($product).' unit', 'Included solar panel, battery, remote or mounting accessories as supplied by the seller package'];
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    public static function faqs(Product $product): array
    {
        $custom = self::faqItems($product);
        if ($custom !== []) {
            return $custom;
        }

        $displayName = self::displayName($product);

        return [
            [
                'question' => 'Is '.$displayName.' available in Kenya?',
                'answer' => $product->stock > 0
                    ? $displayName.' is currently listed as available. Stock can change, so confirm availability before placing a large order.'
                    : $displayName.' is currently listed as out of stock. Contact the seller to confirm the next availability date.',
            ],
            [
                'question' => 'What is the current price of '.$displayName.'?',
                'answer' => 'The current listed price is KSh '.number_format((float) $product->price, 2).'. Prices come from the product catalogue and may change when inventory is updated.',
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public static function comparisonLinks(Product $product): array
    {
        $pairs = [
            ['100w', '200w', '100W vs 200W Solar Flood Lights'],
            ['200w', '300w', '200W vs 300W Solar Flood Lights'],
            ['motion-sensor', '100w', 'Motion Sensor vs Standard Solar Lights'],
            ['all-in-one', 'split', 'All-in-One vs Split Solar Lights'],
        ];

        $haystack = Str::lower($product->name.' '.$product->slug.' '.$product->sku);
        $links = [];

        foreach ($pairs as [$left, $right, $label]) {
            if (! Str::contains($haystack, [$left, $right])) {
                continue;
            }

            $otherNeedle = Str::contains($haystack, $left) ? $right : $left;
            $otherProduct = Product::query()
                ->active()
                ->where(function ($query) use ($otherNeedle): void {
                    $query->where('slug', 'like', '%'.$otherNeedle.'%')
                        ->orWhere('name', 'like', '%'.$otherNeedle.'%')
                        ->orWhere('sku', 'like', '%'.$otherNeedle.'%');
                })
                ->first();

            if ($otherProduct) {
                $links[] = [
                    'label' => $label,
                    'url' => route('comparison.show', Str::slug($label)),
                ];
            }
        }

        return $links;
    }

    public static function youtubeVideoId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (preg_match('/(?:youtube\.com|youtube-nocookie\.com)\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/)([A-Za-z0-9_-]{6,})/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/youtu\.be\/([A-Za-z0-9_-]{6,})/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function youtubeEmbedUrl(?string $url): ?string
    {
        $videoId = self::youtubeVideoId($url);

        return $videoId ? 'https://www.youtube-nocookie.com/embed/'.$videoId : null;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private static function faqItems(Product $product): array
    {
        if (! self::columnReady($product, 'faq_items') || ! is_array($product->faq_items)) {
            return [];
        }

        $items = [];
        foreach ($product->faq_items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            if ($question !== '' && $answer !== '') {
                $items[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private static function linesFromColumn(Product $product, string $column): array
    {
        $value = self::columnValue($product, $column);
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $line): string => trim(strip_tags($line)),
            preg_split('/\r\n|\r|\n/', $value) ?: []
        )));
    }

    private static function columnValue(Product $product, string $column): ?string
    {
        if (! self::columnReady($product, $column)) {
            return null;
        }

        $value = trim((string) ($product->{$column} ?? ''));

        return $value !== '' ? $value : null;
    }

    private static function columnReady(Product $product, string $column): bool
    {
        static $cache = [];
        $key = $product->getTable().'.'.$column;

        return $cache[$key] ??= Schema::hasColumn($product->getTable(), $column);
    }
}
