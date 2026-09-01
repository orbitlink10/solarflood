@extends('admin.layout')

@push('head')
    <script src="{{ asset('assets/product-editor.js') }}" defer></script>
@endpush

@section('content')
@php
    $categoryToEdit = $categoryToEdit ?? null;
    $isEditingCategory = $categoryToEdit instanceof \App\Models\Category;
    $selectedParentId = old('parent_id', $defaultParentId);
    if ($isEditingCategory) {
        $selectedParentId = old('parent_id', $categoryToEdit->parent_id);
    }
    $showOptionalCategorySettings = filled($selectedParentId) || $errors->has('image') || filled($categoryToEdit?->image_url);
    $categorySeoFieldsReady = $categorySeoFieldsReady ?? false;
    $categoryFaqItems = old('faq_items', $categoryToEdit?->faq_items ?? [
        ['question' => '', 'answer' => ''],
        ['question' => '', 'answer' => ''],
        ['question' => '', 'answer' => ''],
    ]);
@endphp
<div class="admin-shell">
    @include('admin.partials.sidebar', ['activeAdminNav' => 'categories'])

    <div class="admin-main admin-management-main">
        @unless($categoryContentFieldsReady)
            <div class="alert error">
                Category content fields are not ready yet. Run <code>php artisan migrate</code> to save meta descriptions and category descriptions.
            </div>
        @endunless

        <section class="admin-page-head admin-page-head--product-create">
            <div>
                <h1 class="admin-page-title">{{ $isEditingCategory ? 'Edit Category' : 'Create Category' }}</h1>
            </div>
        </section>

        <section class="panel admin-product-create-panel">
            <form class="admin-product-create-form" method="post" action="{{ $isEditingCategory ? route('admin.categories.update', $categoryToEdit) : route('admin.categories.store') }}" enctype="multipart/form-data">
                @csrf
                @if($isEditingCategory)
                    @method('PUT')
                @endif

                <div class="admin-product-field">
                    <label class="admin-product-label" for="name">
                        Name <span class="admin-field-required">*</span>
                    </label>
                    <input
                        class="admin-product-input"
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $categoryToEdit?->name) }}"
                        placeholder="Enter category name"
                        required
                    >
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="meta_description">Meta description</label>
                    <textarea
                        class="admin-product-input admin-product-textarea"
                        id="meta_description"
                        name="meta_description"
                        rows="4"
                        placeholder="Enter category meta description"
                    >{{ old('meta_description', $categoryToEdit?->meta_description) }}</textarea>
                </div>

                <div class="admin-product-field">
                    <span class="admin-product-label">Description (Optional)</span>

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
                            data-placeholder="Enter category description"
                            contenteditable="true"
                        ></div>

                        <textarea class="rich-editor-input" name="description" hidden>{{ old('description', $categoryToEdit?->description) }}</textarea>
                    </div>
                </div>

                @if($categorySeoFieldsReady)
                    <details class="admin-product-optional-panel">
                        <summary>SEO Settings</summary>
                        <div class="admin-product-optional-body">
                            <label class="admin-product-label" for="seo_title">SEO title</label>
                            <input class="admin-product-input" id="seo_title" type="text" name="seo_title" value="{{ old('seo_title', $categoryToEdit?->seo_title) }}" maxlength="180">

                            <label class="admin-product-label" for="primary_keyword">Primary keyword</label>
                            <input class="admin-product-input" id="primary_keyword" type="text" name="primary_keyword" value="{{ old('primary_keyword', $categoryToEdit?->primary_keyword) }}" maxlength="180" placeholder="e.g. solar flood lights price in Kenya">

                            <label class="admin-product-label" for="canonical_url">Canonical URL override</label>
                            <input class="admin-product-input" id="canonical_url" type="url" name="canonical_url" value="{{ old('canonical_url', $categoryToEdit?->canonical_url) }}" placeholder="Leave empty to use the category URL">

                            <label class="admin-product-label" for="robots">Indexing</label>
                            <select class="admin-product-input admin-product-select" id="robots" name="robots">
                                <option value="" @selected(old('robots', $categoryToEdit?->robots) === null)>Use default</option>
                                <option value="index,follow" @selected(old('robots', $categoryToEdit?->robots) === 'index,follow')>Index, follow</option>
                                <option value="noindex,follow" @selected(old('robots', $categoryToEdit?->robots) === 'noindex,follow')>Noindex, follow</option>
                            </select>

                            <label class="admin-product-label" for="schema_type">Schema type</label>
                            <input class="admin-product-input" id="schema_type" type="text" name="schema_type" value="{{ old('schema_type', $categoryToEdit?->schema_type) }}" maxlength="80" placeholder="CollectionPage">

                            <label class="admin-product-label" for="sitemap_enabled">XML sitemap</label>
                            <select class="admin-product-input admin-product-select" id="sitemap_enabled" name="sitemap_enabled">
                                <option value="1" @selected((string) old('sitemap_enabled', $categoryToEdit?->sitemap_enabled) === '1')>Include in sitemap</option>
                                <option value="0" @selected((string) old('sitemap_enabled', $categoryToEdit?->sitemap_enabled) === '0')>Exclude from sitemap</option>
                            </select>

                            <label class="admin-product-label" for="og_title">Open Graph title</label>
                            <input class="admin-product-input" id="og_title" type="text" name="og_title" value="{{ old('og_title', $categoryToEdit?->og_title) }}" maxlength="180">

                            <label class="admin-product-label" for="og_description">Open Graph description</label>
                            <textarea class="admin-product-input admin-product-textarea" id="og_description" name="og_description" rows="3">{{ old('og_description', $categoryToEdit?->og_description) }}</textarea>

                            <label class="admin-product-label" for="og_image">Open Graph image URL</label>
                            <input class="admin-product-input" id="og_image" type="url" name="og_image" value="{{ old('og_image', $categoryToEdit?->og_image) }}">

                            <label class="admin-product-label" for="intro">Category intro</label>
                            <textarea class="admin-product-input admin-product-textarea" id="intro" name="intro" rows="3">{{ old('intro', $categoryToEdit?->intro) }}</textarea>

                            <label class="admin-product-label" for="seo_content">Category SEO content</label>
                            <textarea class="admin-product-input admin-product-textarea" id="seo_content" name="seo_content" rows="6">{{ old('seo_content', $categoryToEdit?->seo_content) }}</textarea>

                            <div class="admin-product-field">
                                <span class="admin-product-label">Category FAQs</span>
                                @foreach($categoryFaqItems as $index => $faqItem)
                                    <input class="admin-product-input" type="text" name="faq_items[{{ $index }}][question]" value="{{ $faqItem['question'] ?? '' }}" placeholder="Question">
                                    <textarea class="admin-product-input admin-product-textarea" name="faq_items[{{ $index }}][answer]" rows="2" placeholder="Answer">{{ $faqItem['answer'] ?? '' }}</textarea>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @endif

                <details class="admin-product-optional-panel" {{ $showOptionalCategorySettings ? 'open' : '' }}>
                    <summary>Parent Category and Image (Optional)</summary>
                    <div class="admin-product-optional-body">
                        <label class="admin-product-label" for="parent_id">Parent Category</label>
                        <select class="admin-product-input admin-product-select" id="parent_id" name="parent_id">
                            <option value="">Top Level Category</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected($selectedParentId == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>

                        @if($categoryToEdit?->image_url)
                            <div class="admin-settings-preview">
                                <img src="{{ $categoryToEdit->image_url }}" alt="{{ $categoryToEdit->name }}">
                            </div>
                        @endif

                        <label class="admin-product-label" for="image">Upload Image</label>
                        <input
                            class="admin-product-file"
                            id="image"
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >
                        <p class="admin-product-optional-copy">Use a parent category only when you are creating a sub category.</p>
                    </div>
                </details>

                <div class="admin-product-actions">
                    <p>Descriptions are optional, but they help when categories need search-friendly and richer content later.</p>
                    <button type="submit" class="admin-primary-pill">{{ $isEditingCategory ? 'Update Category' : 'Save Category' }}</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
