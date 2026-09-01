<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Page;
use Illuminate\Support\Str;

class SeoUrl
{
    /**
     * @param  array<string, mixed>  $query
     */
    public static function path(string $path, array $query = [], bool $canonical = false): string
    {
        $path = SeoPageRegistry::normalizePath($path);
        $query = array_filter($query, fn (mixed $value): bool => $value !== null && $value !== '');

        if ($query !== []) {
            $path .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $canonical ? CanonicalUrl::normalize($path) : url($path);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function category(Category|string $category, array $query = [], bool $canonical = false): string
    {
        $slug = $category instanceof Category ? $category->slug : (string) $category;
        $path = SeoPageRegistry::pathForCategorySlug($slug);

        if ($path) {
            return self::path($path, $query, $canonical);
        }

        if ($category instanceof Category) {
            return $canonical
                ? CanonicalUrl::route('category.show', $category, $query)
                : route('category.show', $category, absolute: true).self::queryString($query);
        }

        return self::path('/category/'.Str::slug($slug), $query, $canonical);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function page(Page|string $page, array $query = [], bool $canonical = false): string
    {
        $path = SeoPageRegistry::pathForPage($page);

        return self::path($path ?: '/'.Str::slug((string) $page), $query, $canonical);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private static function queryString(array $query): string
    {
        $query = array_filter($query, fn (mixed $value): bool => $value !== null && $value !== '');

        return $query === [] ? '' : '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
