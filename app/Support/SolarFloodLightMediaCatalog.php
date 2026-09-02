<?php

namespace App\Support;

class SolarFloodLightMediaCatalog
{
    /**
     * Comparable public product images for seeded solar lighting categories.
     *
     * @return array<string, string>
     */
    public static function categoryImages(): array
    {
        return [
            'solar-flood-lights' => 'https://www.hoangquocbao.com/library/9021_1.webp',
            'solar-flood-lights-100w' => 'https://renovating.us/cdn/shop/products/24_43c99466-14bf-45e7-9987-87f0cc1e7b68.jpg?v=1675608950',
            'solar-flood-lights-200w' => 'https://www.hoangquocbao.com/library/9021_1.webp',
            'solar-flood-lights-300w' => 'https://www.zenith-lighting.com/uploads/202325695/300w-solar-flood-lightb17f1939-ef17-4f86-925b-635e456d649b.jpg',
            'solar-motion-sensor-lights' => 'https://img.waimaoniu.net/1323/1323-201911091222198909.jpg',
            'solar-outdoor-lights' => 'https://cdn.manomano.com/images/images_products/43178709/P/184863866_1.jpg',
            'solar-security-lights' => 'https://tasawwur.ae/storage/main/products/v-max-led-solar-flood-light-500watt-white-600x600.jpg',
            'solar-street-lights' => 'https://www.tdk.co.ke/wp-content/uploads/2021/05/Solar-street-light-with-PIR-motion-and-night-Sensor-90w-11.jpeg',
        ];
    }

    /**
     * Comparable public product images keyed by seeded product SKU.
     *
     * @return array<string, string>
     */
    public static function productImagesBySku(): array
    {
        return [
            'SFL-100W-PANEL' => 'https://renovating.us/cdn/shop/products/24_43c99466-14bf-45e7-9987-87f0cc1e7b68.jpg?v=1675608950',
            'SFL-200W-SPLIT' => 'https://www.hoangquocbao.com/library/9021_1.webp',
            'SFL-300W-COM' => 'https://www.zenith-lighting.com/uploads/202325695/300w-solar-flood-lightb17f1939-ef17-4f86-925b-635e456d649b.jpg',
            'SSL-60W-PIR' => 'https://img.waimaoniu.net/1323/1323-201911091222198909.jpg',
            'SSL-90W-AIO' => 'https://www.tdk.co.ke/wp-content/uploads/2021/05/Solar-street-light-with-PIR-motion-and-night-Sensor-90w-11.jpeg',
            'SFL-500W-HM' => 'https://tasawwur.ae/storage/main/products/v-max-led-solar-flood-light-500watt-white-600x600.jpg',
            'SGW-4PK' => 'https://cdn.manomano.com/images/images_products/43178709/P/184863866_1.jpg',
            'SFL-POLE-KIT' => 'https://www.s-tech.com.au/wp-content/uploads/2025/01/Web-cover-FL-PC-M-1024x1024.png',
        ];
    }

    public static function productImageForSku(?string $sku): ?string
    {
        $sku = trim((string) $sku);

        return $sku !== '' ? self::productImagesBySku()[$sku] ?? null : null;
    }
}
