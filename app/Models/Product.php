<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'description',
        'meta_description',
        'primary_keyword',
        'price',
        'compare_at_price',
        'stock',
        'sku',
        'status',
        'seo_title',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
        'schema_type',
        'sitemap_enabled',
        'model_number',
        'brand',
        'key_use',
        'key_specifications',
        'use_cases',
        'technical_specifications',
        'whats_in_box',
        'recommended_applications',
        'choose_another_model',
        'compatibility',
        'power_requirements',
        'warranty_info',
        'delivery_info',
        'payment_info',
        'faq_items',
        'official_image_url',
        'official_gallery_images',
        'official_video_url',
        'official_media_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'faq_items' => 'array',
        'official_gallery_images' => 'array',
        'official_media_synced_at' => 'datetime',
        'sitemap_enabled' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function seoFieldsReady(): bool
    {
        $table = (new static)->getTable();

        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'seo_title')
            && Schema::hasColumn($table, 'primary_keyword')
            && Schema::hasColumn($table, 'schema_type')
            && Schema::hasColumn($table, 'sitemap_enabled')
            && Schema::hasColumn($table, 'model_number')
            && Schema::hasColumn($table, 'faq_items');
    }

    public static function officialMediaFieldsReady(): bool
    {
        $table = (new static)->getTable();

        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'official_image_url')
            && Schema::hasColumn($table, 'official_gallery_images')
            && Schema::hasColumn($table, 'official_video_url');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereHas('vendor', fn (Builder $builder) => $builder->where('is_approved', true));
    }
}
