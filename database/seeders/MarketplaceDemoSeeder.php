<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomepageContent;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Solar Lighting Admin',
                'phone' => '+254700000001',
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('admin123'),
            ]
        );

        $vendorUser = User::updateOrCreate(
            ['email' => 'vendor@solarfloodlights.test'],
            [
                'name' => 'Solar Vendor Owner',
                'phone' => '+254700000002',
                'role' => 'vendor',
                'status' => 'active',
                'password' => Hash::make('vendor123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@solarfloodlights.test'],
            [
                'name' => 'Sample Customer',
                'phone' => '+254700000003',
                'role' => 'customer',
                'status' => 'active',
                'password' => Hash::make('customer123'),
            ]
        );

        $vendor = Vendor::updateOrCreate(
            ['user_id' => $vendorUser->id],
            [
                'shop_name' => 'Solar Flood Lights Kenya Store',
                'slug' => 'solar-flood-lights-kenya-store',
                'description' => 'Solar flood lights, street lights and outdoor solar lighting accessories',
                'phone' => '+254700111222',
                'address' => 'Nairobi CBD',
                'is_approved' => true,
            ]
        );

        $samples = [
            [
                'category' => 'solar-flood-lights',
                'name' => '100W Solar Flood Light with Panel',
                'model_number' => 'SFL-100W',
                'brand' => 'SolarMax',
                'sku' => 'SFL-100W-PANEL',
                'price' => 8500,
                'compare_at_price' => 9800,
                'stock' => 25,
                'key_use' => 'Home compound, gate and small yard lighting',
                'meta_description' => '100W solar flood light with panel for homes, gates and small compounds in Kenya.',
                'description' => '<p>Compact 100W solar flood light for homes, gates, walkways and small outdoor spaces. Includes solar charging support for night lighting where grid power is unreliable or costly.</p>',
                'technical_specifications' => "Wattage: 100W\nPanel: Solar charging panel included\nBattery: Rechargeable lithium battery\nLighting mode: Remote and dusk-to-dawn modes\nWeather rating: Outdoor use",
                'use_cases' => "Home compounds\nGate entrances\nSmall parking areas",
                'recommended_applications' => "Residential outdoor lighting\nBackup security lighting\nWalkway and driveway lighting",
                'whats_in_box' => "100W solar flood light\nSolar panel\nRemote control\nMounting accessories",
                'power_requirements' => 'Mount the solar panel where it receives direct sunlight for reliable charging and longer night runtime.',
            ],
            [
                'category' => 'solar-flood-lights',
                'name' => '200W Split Solar Flood Light with Remote',
                'model_number' => 'SFL-200W-SPLIT',
                'brand' => 'SolarMax',
                'sku' => 'SFL-200W-SPLIT',
                'price' => 14500,
                'compare_at_price' => 16900,
                'stock' => 18,
                'key_use' => 'Medium compound, shop front and parking yard lighting',
                'meta_description' => '200W split solar flood light with remote control for compounds, shops and parking areas in Kenya.',
                'description' => '<p>Bright 200W split solar flood light with a separate panel for flexible placement. Suitable for wider compounds, business frontages, farm stores and parking spaces.</p>',
                'technical_specifications' => "Wattage: 200W\nPanel: Separate solar panel\nControl: Remote control included\nBattery: Rechargeable lithium battery\nMounting: Wall or pole mount",
                'use_cases' => "Medium compounds\nShop fronts\nParking yards",
                'recommended_applications' => "Residential security lighting\nCommercial outdoor lighting\nFarm and store lighting",
                'whats_in_box' => "200W flood light\nSeparate solar panel\nRemote control\nMounting bracket",
                'choose_another_model' => 'Choose a lower wattage model for small gates and walkways, or a 300W plus model for wider commercial areas.',
            ],
            [
                'category' => 'solar-outdoor-lights',
                'name' => '300W Commercial Solar Flood Light',
                'model_number' => 'SFL-300W-COM',
                'brand' => 'BrightSun',
                'sku' => 'SFL-300W-COM',
                'price' => 24000,
                'compare_at_price' => 27900,
                'stock' => 12,
                'key_use' => 'Large yard, farm and commercial perimeter lighting',
                'meta_description' => '300W commercial solar flood light for large yards, farms, churches and business compounds in Kenya.',
                'description' => '<p>High-output 300W solar flood light for broader outdoor coverage. Built for commercial compounds, farms, schools, churches and areas that need strong night visibility.</p>',
                'technical_specifications' => "Wattage: 300W\nPanel: High-capacity solar panel\nBattery: Rechargeable battery pack\nCoverage: Wide outdoor beam\nInstallation: Pole or wall mount",
                'use_cases' => "Large yards\nFarm stores\nCommercial compounds",
                'recommended_applications' => "Perimeter security lighting\nOutdoor work areas\nInstitution and church compounds",
                'whats_in_box' => "300W solar flood light\nSolar panel\nRemote control\nMounting hardware",
            ],
            [
                'category' => 'solar-motion-sensor-lights',
                'name' => '60W Motion Sensor Solar Security Light',
                'model_number' => 'SSL-60W-PIR',
                'brand' => 'Kenlight Solar',
                'sku' => 'SSL-60W-PIR',
                'price' => 4500,
                'compare_at_price' => 5400,
                'stock' => 40,
                'key_use' => 'Automatic lighting for gates, paths and entrances',
                'meta_description' => '60W motion sensor solar security light for gates, entrances, paths and walls in Kenya.',
                'description' => '<p>Motion sensor solar light for automatic lighting when movement is detected. Useful for gates, paths, stairways, small compounds and wall-mounted security points.</p>',
                'technical_specifications' => "Wattage: 60W\nSensor: PIR motion sensor\nModes: Dim and full-brightness activation\nBattery: Rechargeable lithium battery\nMounting: Wall mount",
                'use_cases' => "Gate entrances\nWalkways\nWall-mounted security points",
                'recommended_applications' => "Energy-saving security lighting\nResidential entrances\nDark paths and stair areas",
                'whats_in_box' => "Motion sensor solar light\nMounting screws\nUser guide",
            ],
            [
                'category' => 'solar-street-lights',
                'name' => '90W All-in-One Solar Street Light',
                'model_number' => 'SSL-90W-AIO',
                'brand' => 'BrightSun',
                'sku' => 'SSL-90W-AIO',
                'price' => 18500,
                'compare_at_price' => 21000,
                'stock' => 14,
                'key_use' => 'Road, estate, school and parking area lighting',
                'meta_description' => '90W all-in-one solar street light for estate roads, schools, churches and parking areas in Kenya.',
                'description' => '<p>All-in-one solar street light with integrated panel and battery housing for clean pole installations. Suitable for estate roads, driveways, schools and public outdoor spaces.</p>',
                'technical_specifications' => "Wattage: 90W\nDesign: All-in-one solar street light\nBattery: Integrated rechargeable battery\nMounting: Pole mount\nApplication: Road and open-area lighting",
                'use_cases' => "Estate roads\nDriveways\nSchool and church compounds",
                'recommended_applications' => "Street and pathway lighting\nParking area lighting\nInstitutional outdoor lighting",
                'whats_in_box' => "All-in-one street light\nMounting arm\nFasteners",
            ],
            [
                'category' => 'solar-security-lights',
                'name' => '500W High Mast Solar Security Flood Light',
                'model_number' => 'SFL-500W-HM',
                'brand' => 'SunGuard',
                'sku' => 'SFL-500W-HM',
                'price' => 52000,
                'compare_at_price' => 58900,
                'stock' => 6,
                'key_use' => 'High-output security lighting for large compounds',
                'meta_description' => '500W high mast solar security flood light for large compounds, warehouses and commercial outdoor areas in Kenya.',
                'description' => '<p>High-output solar security flood light for wide-area night coverage. Designed for warehouses, yards, institutions and commercial compounds that need strong perimeter visibility.</p>',
                'technical_specifications' => "Wattage: 500W\nApplication: High mast and wide-area lighting\nPanel: High-capacity solar panel\nBattery: Large rechargeable battery pack\nMounting: Pole or high wall mount",
                'use_cases' => "Warehouses\nLarge commercial yards\nInstitution compounds",
                'recommended_applications' => "Perimeter security lighting\nCCTV area support\nLarge outdoor spaces",
                'whats_in_box' => "500W solar flood light\nSolar panel\nRemote control\nMounting hardware",
                'choose_another_model' => 'Choose 100W to 300W models for smaller compounds where high mast coverage is not required.',
            ],
            [
                'category' => 'solar-outdoor-lights',
                'name' => 'Solar Garden Wall Light Pack of 4',
                'model_number' => 'SGW-4PK',
                'brand' => 'Kenlight Solar',
                'sku' => 'SGW-4PK',
                'price' => 3800,
                'compare_at_price' => 4600,
                'stock' => 35,
                'key_use' => 'Decorative wall, garden and pathway lighting',
                'meta_description' => 'Pack of four solar garden wall lights for paths, patios, balconies and home compounds in Kenya.',
                'description' => '<p>Set of compact solar wall lights for gardens, walkways, patios and balconies. Good for softer accent lighting and practical path visibility.</p>',
                'technical_specifications' => "Pack size: 4 lights\nCharging: Solar charging\nMounting: Wall or fence mount\nApplication: Garden and pathway lighting\nBattery: Rechargeable internal battery",
                'use_cases' => "Garden paths\nPatios and balconies\nFence and wall lighting",
                'recommended_applications' => "Decorative outdoor lighting\nPathway visibility\nResidential wall lighting",
                'whats_in_box' => "4 solar wall lights\nMounting screws",
            ],
            [
                'category' => 'solar-flood-lights',
                'name' => 'Solar Flood Light Mounting Pole Kit',
                'model_number' => 'SFL-POLE-KIT',
                'brand' => 'SunGuard',
                'sku' => 'SFL-POLE-KIT',
                'price' => 6500,
                'compare_at_price' => 7800,
                'stock' => 20,
                'key_use' => 'Mounting support for solar flood lights and panels',
                'meta_description' => 'Solar flood light mounting pole kit for cleaner outdoor lighting installation in Kenya.',
                'description' => '<p>Mounting pole kit for solar flood lights and panels. Useful when wall mounting is not ideal or when better panel exposure is needed.</p>',
                'technical_specifications' => "Type: Mounting accessory\nUse: Solar flood light and panel support\nMaterial: Outdoor installation hardware\nCompatibility: Confirm fixture and panel size before purchase",
                'use_cases' => "Pole mounting\nPanel exposure improvement\nOutdoor lighting installation",
                'recommended_applications' => "Solar flood light installation\nStreet light support\nFarm and compound lighting",
                'whats_in_box' => "Mounting pole kit\nBrackets\nFasteners",
            ],
        ];

        foreach ($samples as $sample) {
            $category = Category::where('slug', $sample['category'])
                ->orWhere('name', $sample['category'])
                ->first();
            if (! $category) {
                continue;
            }

            $existingProduct = Product::where('sku', $sample['sku'])->first();
            $slug = $existingProduct?->slug ?: $this->uniqueProductSlug($sample['name']);

            $product = Product::updateOrCreate(
                ['sku' => $sample['sku']],
                [
                    'vendor_id' => $vendor->id,
                    'category_id' => $category->id,
                    'slug' => $slug,
                    'name' => $sample['name'],
                    'description' => $sample['description'],
                    'meta_description' => $sample['meta_description'],
                    'price' => $sample['price'],
                    'compare_at_price' => $sample['compare_at_price'],
                    'stock' => $sample['stock'],
                    'status' => 'active',
                    'model_number' => $sample['model_number'],
                    'brand' => $sample['brand'],
                    'key_use' => $sample['key_use'],
                    'technical_specifications' => $sample['technical_specifications'],
                    'use_cases' => $sample['use_cases'],
                    'recommended_applications' => $sample['recommended_applications'],
                    'whats_in_box' => $sample['whats_in_box'],
                    'compatibility' => 'Confirm mounting height, panel exposure, battery expectations and weather exposure before purchase.',
                    'power_requirements' => $sample['power_requirements'] ?? 'Install the solar panel in direct sunlight and confirm charging time for the expected night runtime.',
                    'warranty_info' => 'Warranty depends on supplier terms, battery type and installation conditions. Confirm coverage before checkout.',
                    'delivery_info' => 'Delivery is available within Kenya, with timelines confirmed by order size, stock location and destination.',
                    'payment_info' => 'Payment options are confirmed at checkout or through the seller before dispatch.',
                    'faq_items' => [
                        [
                            'question' => 'Is '.$sample['name'].' available in Kenya?',
                            'answer' => $sample['name'].' is listed in the catalogue with current stock status and pricing. Confirm availability before placing a bulk order.',
                        ],
                        [
                            'question' => 'Does '.$sample['name'].' need grid electricity?',
                            'answer' => 'No. It is designed for solar-powered lighting, but runtime depends on sunlight, battery capacity, mounting position and lighting mode.',
                        ],
                    ],
                ]
            );

            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'is_primary' => true],
                [
                    'image_url' => 'assets/product-placeholder.svg',
                    'sort_order' => 0,
                ]
            );
        }

        HomepageContent::updateOrCreate(
            ['site_key' => HomepageContent::DEFAULT_SITE_KEY],
            [
                'contact_phone' => '+254700111222',
                'contact_whatsapp' => '+254700111222',
                'contact_email' => 'sales@solarfloodlights.test',
                'hero_title' => 'Solar Flood Lights Kenya',
                'hero_description' => 'Shop solar flood lights, motion sensor lights, solar street lights and accessories for reliable outdoor lighting across Kenya.',
                'why_choose_title' => 'Why Buy Solar Flood Lights From Us?',
                'why_choose_intro' => 'Compare outdoor solar lighting products by wattage, battery notes, stock, price and installation use.',
                'faq_title' => 'Solar Flood Light Buying Questions',
                'faq_intro' => 'Quick answers about price, wattage, runtime, delivery and installation planning.',
                'content_badge' => 'Solar Lighting Guide',
                'content_title' => 'Solar Flood Lights in Kenya',
                'content_intro' => 'Choose lights based on area size, brightness, battery capacity and mounting location.',
                'content_body' => '<h2>Solar flood lights in Kenya</h2><p>Solar flood lights help light gates, compounds, farms, schools, churches, parking yards and business premises without relying fully on grid electricity.</p><p>Compare wattage, battery capacity, panel size, mounting style, weather rating and expected runtime before buying.</p><h3>Common buying considerations</h3><ul><li>Use lower wattage lights for paths, small gates and balconies.</li><li>Use 100W to 300W lights for compounds, yards and parking areas.</li><li>Use high-output or street-light models for institutions, roads and large commercial spaces.</li></ul>',
            ]
        );

        foreach ([
            [
                'name' => 'Joan K., Nairobi',
                'role' => 'Home compound lighting',
                'quote' => 'The product details made it easy to choose a light for our gate and driveway before ordering.',
                'rating' => 5,
                'sort_order' => 1,
            ],
            [
                'name' => 'Samuel O., Meru',
                'role' => 'Farm security lighting',
                'quote' => 'We compared wattage and stock online, then ordered lights for the store and walkway.',
                'rating' => 5,
                'sort_order' => 2,
            ],
            [
                'name' => 'Victor M., Rongai',
                'role' => 'Business yard lighting',
                'quote' => 'The solar security lights improved night visibility around the parking area without extra power wiring.',
                'rating' => 5,
                'sort_order' => 3,
            ],
        ] as $testimonial) {
            Testimonial::updateOrCreate(
                ['name' => $testimonial['name']],
                $testimonial + ['is_active' => true]
            );
        }

        if (! $admin->isAdmin()) {
            $admin->update(['role' => 'admin']);
        }
    }

    private function uniqueProductSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
