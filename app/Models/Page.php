<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_title',
        'meta_description',
        'primary_keyword',
        'title',
        'heading_two',
        'slug',
        'image_url',
        'alt_text',
        'type',
        'body',
        'seo_title',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
        'schema_type',
        'sitemap_enabled',
        'faq_items',
    ];

    protected $casts = [
        'faq_items' => 'array',
        'sitemap_enabled' => 'boolean',
    ];

    public static function storageReady(): bool
    {
        return Schema::hasTable((new static)->getTable());
    }

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
            && Schema::hasColumn($table, 'canonical_url')
            && Schema::hasColumn($table, 'schema_type')
            && Schema::hasColumn($table, 'sitemap_enabled')
            && Schema::hasColumn($table, 'faq_items');
    }
}
