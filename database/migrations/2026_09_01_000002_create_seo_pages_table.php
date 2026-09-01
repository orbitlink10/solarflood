<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('registry_id')->unique();
            $table->string('page_type', 80);
            $table->string('slug', 180)->unique();
            $table->string('full_url')->unique();
            $table->string('primary_keyword', 180);
            $table->string('primary_keyword_normalized', 180)->unique();
            $table->string('seo_title', 180)->unique();
            $table->string('h1', 180)->unique();
            $table->string('canonical_url');
            $table->boolean('indexable')->default(false);
            $table->boolean('follow')->default(true);
            $table->boolean('sitemap_enabled')->default(false);
            $table->string('parent_page', 180)->nullable();
            $table->string('status', 40)->default('planned');
            $table->string('schema_type', 80)->nullable();
            $table->string('content_model_type')->nullable();
            $table->unsignedBigInteger('content_model_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['content_model_type', 'content_model_id']);
            $table->index(['page_type', 'status']);
            $table->index(['indexable', 'sitemap_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
