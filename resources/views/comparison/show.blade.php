@extends('layouts.app')

@php
    $description = 'Compare ' . $title . ' for solar lighting buyers in Kenya, including current catalogue prices, stock status and product details.';
    $breadcrumbSchema = \App\Support\StructuredData::breadcrumbs([
        ['name' => 'Home', 'url' => \App\Support\CanonicalUrl::route('home')],
        ['name' => $title, 'url' => $canonicalUrl],
    ]);
    $productImageFallback = \App\Support\ProductImageCatalog::placeholderUrl();
@endphp

@section('title', $title . ' | Solar Flood Lights Kenya')
@section('meta_description', $description)
@section('canonical_url', $canonicalUrl)
@section('og_title', $title . ' | Solar Flood Lights Kenya')
@section('og_description', $description)

@push('head')
    <script type="application/ld+json">@json($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
@endpush

@section('content')
<article class="comparison-page">
    <nav class="product-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span>/</span>
        <span>{{ $title }}</span>
    </nav>

    <section class="panel comparison-head">
        <p class="catalog-search-eyebrow">Solar lighting comparison</p>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
    </section>

    <section class="panel">
        <div class="table-wrap">
            <table class="router-price-table">
                <thead>
                <tr>
                    <th>Model</th>
                    <th>Current Price</th>
                    <th>Key Use</th>
                    <th>Availability</th>
                    <th>Category</th>
                </tr>
                </thead>
                <tbody>
                @foreach($products as $product)
                    <tr>
                        <td><a href="{{ route('product.show', $product) }}">{{ \App\Support\ProductSeo::model($product) }}</a></td>
                        <td>KSh {{ number_format((float) $product->price, 2) }}</td>
                        <td>{{ \App\Support\ProductSeo::keyUse($product) }}</td>
                        <td>{{ $product->stock > 0 ? 'In stock' : 'Out of stock' }}</td>
                        <td>
                            @if($product->category)
                                <a href="{{ route('category.show', $product->category) }}">{{ $product->category->name }}</a>
                            @else
                                General
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="products-grid">
        @foreach($products as $product)
            @include('partials.product-card', ['product' => $product, 'productImageFallback' => $productImageFallback])
        @endforeach
    </section>
</article>
@endsection
