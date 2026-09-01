<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'products', 'pages'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'primary_keyword')) {
                    $table->string('primary_keyword', 180)->nullable()->after('meta_description');
                }

                if (! Schema::hasColumn($tableName, 'schema_type')) {
                    $table->string('schema_type', 80)->nullable()->after('og_image');
                }

                if (! Schema::hasColumn($tableName, 'sitemap_enabled')) {
                    $table->boolean('sitemap_enabled')->default(true)->after('schema_type');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['categories', 'products', 'pages'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $columns = array_values(array_filter(
                    ['primary_keyword', 'schema_type', 'sitemap_enabled'],
                    fn (string $column): bool => Schema::hasColumn($tableName, $column)
                ));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
