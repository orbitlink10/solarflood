<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'registry_id',
        'page_type',
        'slug',
        'full_url',
        'primary_keyword',
        'primary_keyword_normalized',
        'seo_title',
        'h1',
        'canonical_url',
        'indexable',
        'follow',
        'sitemap_enabled',
        'parent_page',
        'status',
        'schema_type',
        'content_model_type',
        'content_model_id',
        'notes',
    ];

    protected $casts = [
        'indexable' => 'boolean',
        'follow' => 'boolean',
        'sitemap_enabled' => 'boolean',
    ];
}
