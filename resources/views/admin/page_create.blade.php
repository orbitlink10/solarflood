@extends('admin.layout')

@push('head')
    <script src="{{ asset('assets/product-editor.js') }}" defer></script>
@endpush

@section('content')
@php
    $pageToEdit = $pageToEdit ?? null;
    $isEditingPage = $pageToEdit instanceof \App\Models\Page;
    $pageSeoFieldsReady = \App\Models\Page::seoFieldsReady();
    $pageFaqItems = old('faq_items', $pageToEdit?->faq_items ?? [
        ['question' => '', 'answer' => ''],
        ['question' => '', 'answer' => ''],
        ['question' => '', 'answer' => ''],
    ]);
@endphp
<div class="admin-shell">
    @include('admin.partials.sidebar', ['activeAdminNav' => 'pages'])

    <div class="admin-main admin-management-main">
        <section class="admin-page-head admin-page-head--product-create">
            <div>
                <h1 class="admin-page-title">{{ $isEditingPage ? 'Edit Page' : 'Manage Pages' }}</h1>
                <p class="admin-page-copy">{{ $isEditingPage ? 'Update the page content below and preview it when ready' : 'Fill in the page details below to publish new content' }}</p>
            </div>
        </section>

        <section class="panel admin-product-create-panel">
            @unless($pagesStorageReady)
                <div class="alert error">
                    Page storage is not ready yet. Run <code>php artisan migrate</code> to create the <code>pages</code> table before saving pages.
                </div>
            @endunless

            <form class="admin-product-create-form" method="post" action="{{ $isEditingPage ? route('admin.pages.update', $pageToEdit) : route('admin.pages.store') }}">
                @csrf
                @if($isEditingPage)
                    @method('PUT')
                @endif

                <div class="admin-product-field">
                    <label class="admin-product-label" for="meta_title">Meta Title</label>
                    <input
                        class="admin-product-input"
                        id="meta_title"
                        type="text"
                        name="meta_title"
                        value="{{ old('meta_title', $pageToEdit?->meta_title) }}"
                        placeholder="Enter Meta Title"
                        @disabled(! $pagesStorageReady)
                        required
                    >
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="title">Page Title</label>
                    <input
                        class="admin-product-input"
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old('title', $pageToEdit?->title) }}"
                        placeholder="Enter Keyword Title"
                        @disabled(! $pagesStorageReady)
                        required
                    >
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="heading_two">Heading 2</label>
                    <input
                        class="admin-product-input"
                        id="heading_two"
                        type="text"
                        name="heading_two"
                        value="{{ old('heading_two', $pageToEdit?->heading_two) }}"
                        placeholder="Enter Heading 2"
                        @disabled(! $pagesStorageReady)
                        required
                    >
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="type">Type</label>
                    <select class="admin-product-input admin-product-select" id="type" name="type" @disabled(! $pagesStorageReady) required>
                        <option value="post" @selected(old('type', $pageToEdit?->type ?? 'post') === 'post')>Post</option>
                        <option value="page" @selected(old('type', $pageToEdit?->type) === 'page')>Page</option>
                    </select>
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="meta_description">Meta Description</label>
                    <textarea
                        class="admin-product-input admin-product-textarea"
                        id="meta_description"
                        name="meta_description"
                        rows="4"
                        placeholder="Write a short search-friendly summary"
                        @disabled(! $pagesStorageReady)
                        required
                    >{{ old('meta_description', $pageToEdit?->meta_description) }}</textarea>
                </div>

                <div class="admin-product-field">
                    <span class="admin-product-label">Page Description</span>

                    <div class="admin-product-editor-shell admin-post-editor-shell" data-rich-editor>
                        <div class="admin-product-editor-menubar">
                            <button type="button" class="admin-product-editor-menu-button">File</button>
                            <button type="button" class="admin-product-editor-menu-button">Edit</button>
                            <button type="button" class="admin-product-editor-menu-button">View</button>
                            <button type="button" class="admin-product-editor-menu-button">Insert</button>
                            <div class="admin-product-editor-menu-group" data-editor-menu>
                                <button
                                    type="button"
                                    class="admin-product-editor-menu-button"
                                    data-menu-trigger
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                >Format</button>
                                <div class="admin-product-editor-dropdown" data-menu-panel hidden>
                                    <button type="button" class="admin-product-editor-dropdown-item" data-menu-command="bold">
                                        <span>Bold</span>
                                        <span class="admin-product-editor-shortcut">Ctrl+B</span>
                                    </button>
                                    <button type="button" class="admin-product-editor-dropdown-item" data-menu-command="italic">
                                        <span>Italic</span>
                                        <span class="admin-product-editor-shortcut">Ctrl+I</span>
                                    </button>
                                    <button type="button" class="admin-product-editor-dropdown-item" data-menu-command="underline">
                                        <span>Underline</span>
                                        <span class="admin-product-editor-shortcut">Ctrl+U</span>
                                    </button>
                                    <button type="button" class="admin-product-editor-dropdown-item" data-menu-command="strikeThrough">
                                        <span>Strikethrough</span>
                                    </button>
                                    <div class="admin-product-editor-dropdown-divider"></div>
                                    <div class="admin-product-editor-menu-group admin-product-editor-menu-group--submenu" data-editor-submenu>
                                        <button
                                            type="button"
                                            class="admin-product-editor-dropdown-item"
                                            data-submenu-trigger
                                            aria-haspopup="true"
                                            aria-expanded="false"
                                        >
                                            <span>Headings</span>
                                            <span class="admin-product-editor-caret">›</span>
                                        </button>
                                        <div class="admin-product-editor-dropdown admin-product-editor-dropdown--submenu" data-submenu-panel hidden>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-format-block="H1">Heading 1</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-format-block="H2">Heading 2</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-format-block="H3">Heading 3</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-format-block="H4">Heading 4</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-format-block="H5">Heading 5</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-format-block="H6">Heading 6</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-format-block="P">Paragraph</button>
                                        </div>
                                    </div>
                                    <div class="admin-product-editor-menu-group admin-product-editor-menu-group--submenu" data-editor-submenu>
                                        <button
                                            type="button"
                                            class="admin-product-editor-dropdown-item"
                                            data-submenu-trigger
                                            aria-haspopup="true"
                                            aria-expanded="false"
                                        >
                                            <span>Align</span>
                                            <span class="admin-product-editor-caret">›</span>
                                        </button>
                                        <div class="admin-product-editor-dropdown admin-product-editor-dropdown--submenu" data-submenu-panel hidden>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-menu-command="justifyLeft">Align left</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-menu-command="justifyCenter">Align center</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-menu-command="justifyRight">Align right</button>
                                            <button type="button" class="admin-product-editor-dropdown-item" data-menu-command="justifyFull">Justify</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="admin-product-editor-menu-button">Tools</button>
                            <button type="button" class="admin-product-editor-menu-button">Table</button>
                        </div>

                        <div class="admin-product-editor-toolbar editor-toolbar">
                            <button type="button" class="admin-product-editor-icon" data-command="undo" aria-label="Undo">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 14 4 9l5-5"></path><path d="M20 20a8 8 0 0 0-8-8H4"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-command="redo" aria-label="Redo">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 14 5-5-5-5"></path><path d="M4 20a8 8 0 0 1 8-8h8"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon admin-product-editor-icon--text" data-command="bold" aria-label="Bold">B</button>
                            <button type="button" class="admin-product-editor-icon admin-product-editor-icon--text admin-product-editor-icon--italic" data-command="italic" aria-label="Italic">I</button>
                            <button type="button" class="admin-product-editor-icon" data-command="justifyLeft" aria-label="Align left">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h14"></path><path d="M4 10h10"></path><path d="M4 14h14"></path><path d="M4 18h10"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-command="justifyCenter" aria-label="Align center">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14"></path><path d="M7 10h10"></path><path d="M5 14h14"></path><path d="M7 18h10"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-command="justifyRight" aria-label="Align right">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h14"></path><path d="M10 10h10"></path><path d="M6 14h14"></path><path d="M10 18h10"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-command="outdent" aria-label="Outdent">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 8H20"></path><path d="M10 12h10"></path><path d="M10 16H20"></path><path d="m4 12 4-4v8l-4-4Z"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-command="indent" aria-label="Indent">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h10"></path><path d="M4 12h10"></path><path d="M4 16h10"></path><path d="m20 12-4 4V8l4 4Z"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-action="link" aria-label="Insert link">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 1 0-7l1.5-1.5a5 5 0 0 1 7 7L17 13"></path><path d="M14 11a5 5 0 0 1 0 7l-1.5 1.5a5 5 0 0 1-7-7L7 11"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-action="image" aria-label="Insert image">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"></rect><path d="m8 15 3-3 3 3 2-2 4 4"></path><path d="M9 10h.01"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-action="media" aria-label="Insert media">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"></rect><path d="m10 9 5 3-5 3V9Z"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-action="code" aria-label="Insert code">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 8-4 4 4 4"></path><path d="m15 8 4 4-4 4"></path></svg>
                            </button>
                            <button type="button" class="admin-product-editor-icon" data-action="fullscreen" aria-label="Fullscreen">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4H4v4"></path><path d="M16 4h4v4"></path><path d="M20 16v4h-4"></path><path d="M4 16v4h4"></path><path d="m9 9-5-5"></path><path d="m15 9 5-5"></path><path d="m15 15 5 5"></path><path d="m9 15-5 5"></path></svg>
                            </button>
                        </div>

                        <div
                            class="admin-product-editor-surface editor-surface"
                            data-editor-surface
                            data-placeholder="Write the page description here..."
                            contenteditable="{{ $pagesStorageReady ? 'true' : 'false' }}"
                        ></div>

                        <textarea class="rich-editor-input" name="body" hidden @disabled(! $pagesStorageReady)>{{ old('body', $pageToEdit?->body) }}</textarea>
                    </div>
                </div>

                @if($pageSeoFieldsReady)
                    <details class="admin-product-optional-panel">
                        <summary>SEO Settings and FAQs</summary>
                        <div class="admin-product-optional-body">
                            <label class="admin-product-label" for="seo_title">SEO title override</label>
                            <input class="admin-product-input" id="seo_title" type="text" name="seo_title" value="{{ old('seo_title', $pageToEdit?->seo_title) }}" maxlength="180">

                            <label class="admin-product-label" for="primary_keyword">Primary keyword</label>
                            <input class="admin-product-input" id="primary_keyword" type="text" name="primary_keyword" value="{{ old('primary_keyword', $pageToEdit?->primary_keyword) }}" maxlength="180" placeholder="e.g. solar flood lights price in Kenya">

                            <label class="admin-product-label" for="canonical_url">Canonical URL override</label>
                            <input class="admin-product-input" id="canonical_url" type="url" name="canonical_url" value="{{ old('canonical_url', $pageToEdit?->canonical_url) }}" placeholder="Leave empty to use the page URL">

                            <label class="admin-product-label" for="robots">Indexing</label>
                            <select class="admin-product-input admin-product-select" id="robots" name="robots">
                                <option value="">Use default</option>
                                <option value="index,follow" @selected(old('robots', $pageToEdit?->robots) === 'index,follow')>Index, follow</option>
                                <option value="noindex,follow" @selected(old('robots', $pageToEdit?->robots) === 'noindex,follow')>Noindex, follow</option>
                            </select>

                            <label class="admin-product-label" for="schema_type">Schema type</label>
                            <input class="admin-product-input" id="schema_type" type="text" name="schema_type" value="{{ old('schema_type', $pageToEdit?->schema_type) }}" maxlength="80" placeholder="BlogPosting, Service, WebPage">

                            <label class="admin-product-label" for="sitemap_enabled">XML sitemap</label>
                            <select class="admin-product-input admin-product-select" id="sitemap_enabled" name="sitemap_enabled">
                                <option value="1" @selected((string) old('sitemap_enabled', $pageToEdit?->sitemap_enabled) === '1')>Include in sitemap</option>
                                <option value="0" @selected((string) old('sitemap_enabled', $pageToEdit?->sitemap_enabled) === '0')>Exclude from sitemap</option>
                            </select>

                            <label class="admin-product-label" for="og_title">Open Graph title</label>
                            <input class="admin-product-input" id="og_title" type="text" name="og_title" value="{{ old('og_title', $pageToEdit?->og_title) }}" maxlength="180">

                            <label class="admin-product-label" for="og_description">Open Graph description</label>
                            <textarea class="admin-product-input admin-product-textarea" id="og_description" name="og_description" rows="3">{{ old('og_description', $pageToEdit?->og_description) }}</textarea>

                            <label class="admin-product-label" for="og_image">Open Graph image URL</label>
                            <input class="admin-product-input" id="og_image" type="url" name="og_image" value="{{ old('og_image', $pageToEdit?->og_image) }}">

                            <div class="admin-product-field">
                                <span class="admin-product-label">Page FAQs</span>
                                @foreach($pageFaqItems as $index => $faqItem)
                                    <input class="admin-product-input" type="text" name="faq_items[{{ $index }}][question]" value="{{ $faqItem['question'] ?? '' }}" placeholder="Question">
                                    <textarea class="admin-product-input admin-product-textarea" name="faq_items[{{ $index }}][answer]" rows="2" placeholder="Answer">{{ $faqItem['answer'] ?? '' }}</textarea>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @endif

                <details class="admin-product-optional-panel">
                    <summary>Optional Slug and Image</summary>
                    <div class="admin-product-optional-body">
                        <label class="admin-product-label" for="slug">Custom Slug</label>
                        <input
                            class="admin-product-input"
                            id="slug"
                            type="text"
                            name="slug"
                            value="{{ old('slug', $pageToEdit?->slug) }}"
                            placeholder="leave blank to generate automatically"
                            @disabled(! $pagesStorageReady)
                        >

                        <label class="admin-product-label" for="image_url">Image URL</label>
                        <input
                            class="admin-product-input"
                            id="image_url"
                            type="url"
                            name="image_url"
                            value="{{ old('image_url', $pageToEdit?->image_url) }}"
                            placeholder="Enter image URL"
                            @disabled(! $pagesStorageReady)
                        >

                        <label class="admin-product-label" for="alt_text">Image Alt Text</label>
                        <input
                            class="admin-product-input"
                            id="alt_text"
                            type="text"
                            name="alt_text"
                            value="{{ old('alt_text', $pageToEdit?->alt_text) }}"
                            placeholder="Describe the image for accessibility"
                            @disabled(! $pagesStorageReady)
                        >
                        <p class="admin-product-optional-copy">Alt text is only required when you add an image.</p>
                    </div>
                </details>

                <div class="admin-product-actions">
                    <p>Choose <strong>Post</strong> for blog-style content or <strong>Page</strong> for evergreen site content.</p>
                    <div class="admin-actions-inline">
                        @if($isEditingPage)
                            <a
                                class="admin-secondary-pill"
                                href="{{ route('pages.show', ['page' => $pageToEdit->slug]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >Preview</a>
                        @endif
                        <button type="submit" class="admin-primary-pill" @disabled(! $pagesStorageReady)>{{ $isEditingPage ? 'Update Page' : 'Save Page' }}</button>
                    </div>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
