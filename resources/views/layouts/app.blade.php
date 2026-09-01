<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    @php
        $homepageBrandContent = \App\Models\HomepageContent::current();
        $siteLogoUrl = $homepageBrandContent->siteLogoUrl();
        $pageTitle = trim($__env->yieldContent('title')) ?: config('app.name', 'Solar Flood Lights Kenya');
        $pageDescription = trim($__env->yieldContent('meta_description')) ?: 'Browse solar flood lights, outdoor security lights and current prices in Kenya.';
        $marketCssVersion = @filemtime(public_path('assets/market.css')) ?: time();
        $canonicalUrl = trim($__env->yieldContent('canonical_url'));
        $robotsContent = trim($__env->yieldContent('robots'));
        $openGraphTitle = trim($__env->yieldContent('og_title')) ?: $pageTitle;
        $openGraphDescription = trim($__env->yieldContent('og_description')) ?: $pageDescription;
        $openGraphImage = trim($__env->yieldContent('og_image')) ?: $siteLogoUrl;
        $openGraphType = trim($__env->yieldContent('og_type')) ?: 'website';
        $organizationSchema = \App\Support\StructuredData::organization($homepageBrandContent);
        $headerPhone = $homepageBrandContent->contactPhone();
        $headerWhatsApp = $homepageBrandContent->contactWhatsApp() ?: $headerPhone;
        $headerEmail = $homepageBrandContent->contactEmail();
        $headerPhoneHref = $headerPhone ? 'tel:'.preg_replace('/[^\d+]+/', '', $headerPhone) : null;
        $headerWhatsAppDigits = preg_replace('/\D+/', '', (string) $headerWhatsApp);

        if (\Illuminate\Support\Str::startsWith($headerWhatsAppDigits, '0')) {
            $headerWhatsAppDigits = '254'.ltrim($headerWhatsAppDigits, '0');
        }

        $headerWhatsAppHref = $headerWhatsAppDigits !== '' ? 'https://wa.me/'.$headerWhatsAppDigits : null;
        $websiteSchema = \App\Support\StructuredData::website();
        $menuCategories = \Illuminate\Support\Facades\Schema::hasTable('categories')
            ? \App\Models\Category::query()->whereNull('parent_id')->with('children')->orderBy('name')->get()
                ->filter(fn (\App\Models\Category $menuCategory): bool => \App\Support\SolarFloodLightSeoCatalog::isSolarCategory($menuCategory))
                ->values()
            : collect();
    @endphp
    <title>{!! $pageTitle !!}</title>
    <meta name="description" content="{!! $pageDescription !!}">
    <link rel="canonical" href="{!! $canonicalUrl !== '' ? $canonicalUrl : \App\Support\CanonicalUrl::current() !!}">
    @if($robotsContent !== '')
        <meta name="robots" content="{!! $robotsContent !!}">
    @endif
    <meta property="og:type" content="{!! $openGraphType !!}">
    <meta property="og:site_name" content="{{ config('app.name', 'Solar Flood Lights Kenya') }}">
    <meta property="og:title" content="{!! $openGraphTitle !!}">
    <meta property="og:description" content="{!! $openGraphDescription !!}">
    <meta property="og:url" content="{!! $canonicalUrl !== '' ? $canonicalUrl : \App\Support\CanonicalUrl::current() !!}">
    @if($openGraphImage)
        <meta property="og:image" content="{{ \App\Support\CanonicalUrl::absoluteAsset($openGraphImage) }}">
    @endif
    <meta name="twitter:card" content="{{ $openGraphImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{!! $openGraphTitle !!}">
    <meta name="twitter:description" content="{!! $openGraphDescription !!}">
    @if($openGraphImage)
        <meta name="twitter:image" content="{{ \App\Support\CanonicalUrl::absoluteAsset($openGraphImage) }}">
    @endif
    <script type="application/ld+json">@json($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    <script type="application/ld+json">@json($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    <link rel="stylesheet" href="{{ asset('assets/market.css') }}?v={{ $marketCssVersion }}">
    @stack('head')
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="top-header">
    <div class="nav-wrap">
        <a href="{{ route('home') }}" class="logo" aria-label="Go to homepage">
            @if($siteLogoUrl)
                <img class="logo-image" src="{{ $siteLogoUrl }}" alt="{{ config('app.name', 'Solar Flood Lights Kenya') }}">
            @else
                <span class="logo-main logo-main--single">{{ config('app.name', 'Solar Flood Lights Kenya') }}</span>
            @endif
        </a>

        <form class="search-form" method="get" action="{{ route('home') }}" role="search">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search solar flood lights, street lights and accessories" aria-label="Search products" autocomplete="off" required>
            <button type="submit">Search</button>
        </form>

        <div class="header-actions">
            <nav class="top-links top-contact-links" aria-label="Contact">
                @if($headerPhone && $headerPhoneHref)
                    <a class="contact-link contact-link--phone" href="{{ $headerPhoneHref }}">Phone {{ $headerPhone }}</a>
                @endif
                @if($headerEmail)
                    <a class="contact-link contact-link--email" href="mailto:{{ $headerEmail }}">Email {{ $headerEmail }}</a>
                @endif
                @if($headerWhatsApp && $headerWhatsAppHref)
                    <a class="contact-link contact-link--whatsapp" href="{{ $headerWhatsAppHref }}" target="_blank" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp">
                        <span class="contact-link-icon" aria-hidden="true">
                            <svg viewBox="0 0 32 32" focusable="false"><path d="M16.004 3C8.832 3 3 8.832 3 16.004c0 2.292.594 4.535 1.725 6.51L3 29l6.664-1.746A12.94 12.94 0 0 0 16.004 29C23.176 29 29 23.168 29 15.996 29 8.832 23.168 3 16.004 3zm0 23.543c-2.083 0-4.124-.56-5.902-1.615l-.424-.251-3.955 1.036 1.056-3.855-.276-.438a10.42 10.42 0 0 1-1.605-5.531c0-5.8 4.722-10.523 10.528-10.523 5.798 0 10.518 4.723 10.518 10.523 0 5.807-4.72 10.654-10.528 10.654zm5.78-7.885c-.317-.159-1.872-.923-2.162-1.028-.29-.106-.501-.159-.712.158-.211.318-.817 1.028-1.002 1.24-.185.211-.37.238-.687.079-.317-.158-1.338-.493-2.549-1.573-.942-.84-1.578-1.878-1.763-2.195-.185-.317-.02-.489.139-.647.142-.142.317-.37.476-.555.158-.185.211-.317.317-.528.105-.211.052-.396-.027-.555-.079-.158-.712-1.715-.975-2.35-.256-.617-.517-.533-.712-.543-.184-.01-.396-.012-.607-.012-.211 0-.555.079-.845.396-.29.317-1.108 1.083-1.108 2.642 0 1.558 1.134 3.064 1.293 3.276.158.211 2.232 3.408 5.408 4.779.755.326 1.345.521 1.805.666.759.241 1.45.207 1.996.126.609-.091 1.872-.765 2.136-1.504.264-.74.264-1.373.185-1.505-.079-.132-.29-.211-.607-.37z" fill="currentColor"/></svg>
                        </span>
                    </a>
                @endif
            </nav>

            <nav class="top-account-links" aria-label="Account">
                <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open navigation menu">
                    <span class="menu-toggle-icon" aria-hidden="true"></span>
                </button>
            </nav>
        </div>
    </div>
</header>

<div class="mobile-menu-backdrop" data-menu-backdrop hidden></div>
<nav id="mobile-menu" class="mobile-menu" aria-label="Main navigation" data-mobile-menu hidden>
    <div class="mobile-menu-head">
        <span class="mobile-menu-title">Menu</span>
        <button type="button" class="mobile-menu-close" data-menu-close aria-label="Close navigation menu">&times;</button>
    </div>
    <ul class="mobile-menu-list">
        <li><a class="mobile-menu-link" href="{{ route('home') }}">Home</a></li>
        @foreach($menuCategories as $menuCategory)
            @if($menuCategory->children->isNotEmpty())
                <li class="mobile-menu-accordion">
                    <button type="button" class="mobile-menu-link mobile-menu-accordion-toggle" aria-expanded="false" aria-controls="mobile-submenu-{{ $menuCategory->id }}">
                        <span>{{ \App\Support\SolarFloodLightSeoCatalog::navLabel($menuCategory) }}</span>
                        <span class="mobile-menu-chevron" aria-hidden="true"></span>
                    </button>
                    <ul id="mobile-submenu-{{ $menuCategory->id }}" class="mobile-menu-submenu" hidden>
                        <li><a class="mobile-menu-sublink" href="{{ route('category.show', $menuCategory) }}">All {{ \App\Support\SolarFloodLightSeoCatalog::navLabel($menuCategory) }}</a></li>
                        @foreach($menuCategory->children as $menuChildCategory)
                            <li><a class="mobile-menu-sublink" href="{{ route('category.show', $menuChildCategory) }}">{{ \App\Support\SolarFloodLightSeoCatalog::navLabel($menuChildCategory) }}</a></li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li><a class="mobile-menu-link" href="{{ route('category.show', $menuCategory) }}">{{ \App\Support\SolarFloodLightSeoCatalog::navLabel($menuCategory) }}</a></li>
            @endif
        @endforeach
        <li><a class="mobile-menu-link" href="{{ route('pages.show', ['page' => 'contact-us']) }}">Contact Us</a></li>
        @auth
            @if(auth()->user()->role === 'admin')
                <li><a class="mobile-menu-link" href="{{ route('admin.dashboard') }}">Admin Dashboard</a></li>
            @elseif(auth()->user()->role === 'vendor')
                <li><a class="mobile-menu-link" href="{{ route('vendor.dashboard') }}">Vendor Dashboard</a></li>
            @endif
            <li>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit" class="mobile-menu-link mobile-menu-logout">Logout</button>
                </form>
            </li>
        @else
            <li><a class="mobile-menu-link" href="{{ route('login') }}">Login</a></li>
            <li><a class="mobile-menu-link" href="{{ route('register') }}">Register</a></li>
        @endauth
    </ul>
</nav>

<main class="container" id="main-content">
    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif
    @yield('content')
</main>

<footer class="footer">
    <nav class="footer-links" aria-label="Footer">
        <a href="{{ route('pages.show', ['page' => 'about-us']) }}">About Us</a>
        <a href="{{ route('pages.show', ['page' => 'contact-us']) }}">Contact Us</a>
        <a href="{{ route('pages.show', ['page' => 'delivery-policy']) }}">Delivery Policy</a>
        <a href="{{ route('pages.show', ['page' => 'returns-policy']) }}">Returns Policy</a>
        <a href="{{ route('pages.show', ['page' => 'warranty-policy']) }}">Warranty Policy</a>
        <a href="{{ route('pages.show', ['page' => 'privacy-policy']) }}">Privacy Policy</a>
        <a href="{{ route('pages.show', ['page' => 'terms-and-conditions']) }}">Terms and Conditions</a>
    </nav>
    <p>&copy; {{ date('Y') }} {{ config('business.name', config('app.name', 'Solar Flood Lights Kenya')) }}</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchForm = document.querySelector('.search-form');
    var searchInput = searchForm ? searchForm.querySelector('input[name="search"]') : null;

    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function (event) {
            if (searchInput.value.trim() === '') {
                event.preventDefault();
                searchInput.setCustomValidity('Please enter a product name to search.');
                searchInput.reportValidity();
                searchInput.setCustomValidity('');
                searchInput.focus();
            }
        });
    }

    var toggle = document.querySelector('.menu-toggle');
    var menu = document.querySelector('[data-mobile-menu]');
    var backdrop = document.querySelector('[data-menu-backdrop]');
    var closeButton = document.querySelector('[data-menu-close]');

    if (!toggle || !menu) {
        return;
    }

    var setMenuState = function (open) {
        menu.hidden = !open;
        if (backdrop) {
            backdrop.hidden = !open;
        }
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        document.documentElement.classList.toggle('menu-is-open', open);
        if (open && closeButton) {
            closeButton.focus();
        }
    };

    toggle.addEventListener('click', function () {
        setMenuState(menu.hidden);
    });

    if (closeButton) {
        closeButton.addEventListener('click', function () {
            setMenuState(false);
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setMenuState(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !menu.hidden) {
            setMenuState(false);
            toggle.focus();
        }
    });

    menu.querySelectorAll('.mobile-menu-accordion-toggle').forEach(function (accordionToggle) {
        accordionToggle.addEventListener('click', function () {
            var submenu = document.getElementById(accordionToggle.getAttribute('aria-controls') || '');
            if (!submenu) {
                return;
            }

            var isOpen = !submenu.hidden;
            submenu.hidden = isOpen;
            accordionToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
    });
});
</script>
</body>
</html>
