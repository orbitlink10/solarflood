@extends('admin.layout')

@push('head')
    <script src="{{ asset('assets/product-editor.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const categorySelect = document.querySelector('[data-product-category]');
            const subcategorySelect = document.querySelector('[data-product-subcategory]');

            if (!(categorySelect instanceof HTMLSelectElement) || !(subcategorySelect instanceof HTMLSelectElement)) {
                return;
            }

            const subcategoriesByParent = @json(
                $categories->mapWithKeys(fn ($category) => [
                    $category->id => $category->children
                        ->map(fn ($subcategory) => ['id' => $subcategory->id, 'name' => $subcategory->name])
                        ->values()
                        ->all(),
                ])
            );
            const oldSubcategoryId = @json(old('subcategory_id'));

            const fillSubcategories = (selectedParentId) => {
                const options = subcategoriesByParent[selectedParentId] ?? [];
                const currentValue = oldSubcategoryId ?? subcategorySelect.dataset.currentValue ?? '';

                subcategorySelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = options.length > 0 ? 'Select Subcategory' : 'No subcategories available';
                subcategorySelect.appendChild(placeholder);

                options.forEach((subcategory) => {
                    const option = document.createElement('option');
                    option.value = String(subcategory.id);
                    option.textContent = subcategory.name;
                    option.selected = String(currentValue) === String(subcategory.id);
                    subcategorySelect.appendChild(option);
                });

                subcategorySelect.disabled = options.length === 0;
                if (options.length === 0) {
                    subcategorySelect.value = '';
                }
            };

            fillSubcategories(categorySelect.value);
            categorySelect.addEventListener('change', () => {
                delete subcategorySelect.dataset.currentValue;
                fillSubcategories(categorySelect.value);
            });
        });
    </script>
@endpush

@section('content')
@php
    $productToEdit = $productToEdit ?? null;
    $isEditingProduct = $productToEdit instanceof \App\Models\Product;
    $productCategory = $productToEdit?->category;
    $selectedCategoryId = old('category_id', $productCategory?->parent_id ?: $productCategory?->id);
    $selectedSubcategoryId = old('subcategory_id', $productCategory?->parent_id ? $productCategory->id : null);
    $initialSubcategories = $categories->firstWhere('id', (int) $selectedCategoryId)?->children ?? collect();
    $showInlineCategoryCreation = $categories->isEmpty() && ! $isEditingProduct;
    $primaryImage = $productToEdit?->images?->firstWhere('is_primary', true) ?? $productToEdit?->images?->first();
    $productSeoFieldsReady = \App\Models\Product::seoFieldsReady();
    $productOfficialMediaFieldsReady = \App\Models\Product::officialMediaFieldsReady();
    $productFaqItems = old('faq_items', $productToEdit?->faq_items ?? [
        ['question' => '', 'answer' => ''],
        ['question' => '', 'answer' => ''],
        ['question' => '', 'answer' => ''],
    ]);
@endphp
<div class="admin-shell">
    @include('admin.partials.sidebar', ['activeAdminNav' => 'products'])

    <div class="admin-main admin-management-main">
        <section class="admin-page-head admin-page-head--product-create">
            <div>
                <h1 class="admin-page-title">{{ $isEditingProduct ? 'Edit Product' : 'Add Product' }}</h1>
                <p class="admin-page-copy">{{ $isEditingProduct ? 'Update the product details below to keep the catalog current' : 'Fill in the product details below to add a new item' }}</p>
            </div>
        </section>

        <section class="panel admin-product-create-panel">
            <form class="admin-product-create-form" method="post" action="{{ $isEditingProduct ? route('admin.products.update', $productToEdit) : route('admin.products.store') }}" enctype="multipart/form-data">
                @csrf
                @if($isEditingProduct)
                    @method('PUT')
                @endif

                <div class="admin-product-field">
                    <label class="admin-product-label" for="name">Product Name</label>
                    <input
                        class="admin-product-input"
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $productToEdit?->name) }}"
                        placeholder="Enter product name"
                        required
                    >
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="price">Price (KES)</label>
                    <input
                        class="admin-product-input"
                        id="price"
                        type="number"
                        name="price"
                        min="0.01"
                        step="0.01"
                        value="{{ old('price', $productToEdit?->price) }}"
                        placeholder="Enter product price"
                        required
                    >
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="compare_at_price">Marked Price (KES)</label>
                    <input
                        class="admin-product-input"
                        id="compare_at_price"
                        type="number"
                        name="compare_at_price"
                        min="0.01"
                        step="0.01"
                        value="{{ old('compare_at_price', $productToEdit?->compare_at_price) }}"
                        placeholder="Enter marked price"
                    >
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="stock">Quantity</label>
                    <input
                        class="admin-product-input"
                        id="stock"
                        type="number"
                        name="stock"
                        min="0"
                        step="1"
                        value="{{ old('stock', $productToEdit?->stock ?? 0) }}"
                        placeholder="Enter product quantity"
                        required
                    >
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="category_id">Category</label>
                    <select
                        class="admin-product-input admin-product-select"
                        id="category_id"
                        name="category_id"
                        data-product-category
                    >
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected($selectedCategoryId == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="subcategory_id">Subcategory</label>
                    <select
                        class="admin-product-input admin-product-select"
                        id="subcategory_id"
                        name="subcategory_id"
                        data-product-subcategory
                        data-current-value="{{ $selectedSubcategoryId }}"
                        @disabled($initialSubcategories->isEmpty())
                    >
                        <option value="">{{ $initialSubcategories->isNotEmpty() ? 'Select Subcategory' : 'No subcategories available' }}</option>
                        @foreach($initialSubcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" @selected($selectedSubcategoryId == $subcategory->id)>{{ $subcategory->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($showInlineCategoryCreation)
                    <div class="admin-product-inline-note">
                        <p class="admin-product-inline-note-title">No categories available yet</p>
                        <p class="admin-product-inline-note-copy">Create the first category here so this product can be filed correctly.</p>
                        <label class="admin-product-label" for="category_name">New Category</label>
                        <input
                            class="admin-product-input"
                            id="category_name"
                            type="text"
                            name="category_name"
                            value="{{ old('category_name') }}"
                            placeholder="Create the first category here"
                        >
                    </div>
                @endif

                <div class="admin-product-field">
                    <label class="admin-product-label" for="meta_description">Meta Description</label>
                    <textarea
                        class="admin-product-input admin-product-textarea"
                        id="meta_description"
                        name="meta_description"
                        rows="4"
                        placeholder="Write a short search-friendly summary"
                    >{{ old('meta_description', $productToEdit?->meta_description) }}</textarea>
                </div>

                <div class="admin-product-field">
                    <label class="admin-product-label" for="description">Description</label>

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
                            id="description"
                            class="admin-product-editor-surface editor-surface"
                            data-editor-surface
                            data-placeholder="Write the product description here..."
                            contenteditable="true"
                        ></div>

                        <textarea class="rich-editor-input" name="description" hidden>{{ old('description', $productToEdit?->description) }}</textarea>
                    </div>
                </div>

                @if($productSeoFieldsReady)
                    <details class="admin-product-optional-panel">
                        <summary>SEO, Specs and FAQs</summary>
                        <div class="admin-product-optional-body">
                            <label class="admin-product-label" for="seo_title">SEO title</label>
                            <input class="admin-product-input" id="seo_title" type="text" name="seo_title" value="{{ old('seo_title', $productToEdit?->seo_title) }}" maxlength="180">

                            <label class="admin-product-label" for="primary_keyword">Primary keyword</label>
                            <input class="admin-product-input" id="primary_keyword" type="text" name="primary_keyword" value="{{ old('primary_keyword', $productToEdit?->primary_keyword) }}" maxlength="180" placeholder="e.g. ALLTOP 200W solar flood light Kenya">

                            <label class="admin-product-label" for="canonical_url">Canonical URL override</label>
                            <input class="admin-product-input" id="canonical_url" type="url" name="canonical_url" value="{{ old('canonical_url', $productToEdit?->canonical_url) }}" placeholder="Leave empty to use the product URL">

                            <label class="admin-product-label" for="robots">Indexing</label>
                            <select class="admin-product-input admin-product-select" id="robots" name="robots">
                                <option value="">Use default</option>
                                <option value="index,follow" @selected(old('robots', $productToEdit?->robots) === 'index,follow')>Index, follow</option>
                                <option value="noindex,follow" @selected(old('robots', $productToEdit?->robots) === 'noindex,follow')>Noindex, follow</option>
                            </select>

                            <label class="admin-product-label" for="schema_type">Schema type</label>
                            <input class="admin-product-input" id="schema_type" type="text" name="schema_type" value="{{ old('schema_type', $productToEdit?->schema_type) }}" maxlength="80" placeholder="Product">

                            <label class="admin-product-label" for="sitemap_enabled">XML sitemap</label>
                            <select class="admin-product-input admin-product-select" id="sitemap_enabled" name="sitemap_enabled">
                                <option value="1" @selected((string) old('sitemap_enabled', $productToEdit?->sitemap_enabled) === '1')>Include in sitemap</option>
                                <option value="0" @selected((string) old('sitemap_enabled', $productToEdit?->sitemap_enabled) === '0')>Exclude from sitemap</option>
                            </select>

                            <label class="admin-product-label" for="og_title">Open Graph title</label>
                            <input class="admin-product-input" id="og_title" type="text" name="og_title" value="{{ old('og_title', $productToEdit?->og_title) }}" maxlength="180">

                            <label class="admin-product-label" for="og_description">Open Graph description</label>
                            <textarea class="admin-product-input admin-product-textarea" id="og_description" name="og_description" rows="3">{{ old('og_description', $productToEdit?->og_description) }}</textarea>

                            <label class="admin-product-label" for="og_image">Open Graph image URL</label>
                            <input class="admin-product-input" id="og_image" type="url" name="og_image" value="{{ old('og_image', $productToEdit?->og_image) }}">

                            @if($productOfficialMediaFieldsReady)
                                <label class="admin-product-label" for="official_image_url">Official product image URL</label>
                                <input class="admin-product-input" id="official_image_url" type="url" name="official_image_url" value="{{ old('official_image_url', $productToEdit?->official_image_url) }}" placeholder="https://example.com/solar-flood-light.jpg">

                                <label class="admin-product-label" for="official_video_url">Product video URL (YouTube)</label>
                                <input class="admin-product-input" id="official_video_url" type="url" name="official_video_url" value="{{ old('official_video_url', $productToEdit?->official_video_url) }}" placeholder="https://www.youtube.com/watch?v=...">
                                <p class="admin-product-note">Leave empty when using uploaded product images or seller-provided media.</p>
                            @endif

                            <div class="admin-form-grid">
                                <div>
                                    <label class="admin-product-label" for="model_number">Model number</label>
                                    <input class="admin-product-input" id="model_number" type="text" name="model_number" value="{{ old('model_number', $productToEdit?->model_number) }}">
                                </div>
                                <div>
                                    <label class="admin-product-label" for="brand">Brand</label>
                                    <input class="admin-product-input" id="brand" type="text" name="brand" value="{{ old('brand', $productToEdit?->brand) }}" placeholder="SolarMax">
                                </div>
                            </div>

                            <label class="admin-product-label" for="key_use">Key use</label>
                            <input class="admin-product-input" id="key_use" type="text" name="key_use" value="{{ old('key_use', $productToEdit?->key_use) }}" placeholder="Outdoor compound, farm or security lighting">

                            @foreach([
                                'key_specifications' => 'Key specifications',
                                'technical_specifications' => 'Technical specifications',
                                'use_cases' => 'Use cases',
                                'recommended_applications' => 'Recommended applications',
                                'whats_in_box' => 'What is in the box',
                            ] as $field => $label)
                                <label class="admin-product-label" for="{{ $field }}">{{ $label }}</label>
                                <textarea class="admin-product-input admin-product-textarea" id="{{ $field }}" name="{{ $field }}" rows="4" placeholder="One item per line">{{ old($field, $productToEdit?->{$field}) }}</textarea>
                            @endforeach

                            @foreach([
                                'choose_another_model' => 'When to choose another model',
                                'compatibility' => 'Compatibility',
                                'power_requirements' => 'Power requirements',
                                'warranty_info' => 'Warranty information',
                                'delivery_info' => 'Delivery information',
                                'payment_info' => 'Payment information',
                            ] as $field => $label)
                                <label class="admin-product-label" for="{{ $field }}">{{ $label }}</label>
                                <textarea class="admin-product-input admin-product-textarea" id="{{ $field }}" name="{{ $field }}" rows="3">{{ old($field, $productToEdit?->{$field}) }}</textarea>
                            @endforeach

                            <div class="admin-product-field">
                                <span class="admin-product-label">Product FAQs</span>
                                @foreach($productFaqItems as $index => $faqItem)
                                    <input class="admin-product-input" type="text" name="faq_items[{{ $index }}][question]" value="{{ $faqItem['question'] ?? '' }}" placeholder="Question">
                                    <textarea class="admin-product-input admin-product-textarea" name="faq_items[{{ $index }}][answer]" rows="2" placeholder="Answer">{{ $faqItem['answer'] ?? '' }}</textarea>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @endif

                <details class="admin-product-optional-panel">
                    <summary>Product Image</summary>
                    <div class="admin-product-optional-body">
                        @if($primaryImage?->image_url)
                            <div class="admin-settings-preview">
                                <img src="{{ $primaryImage->publicUrl() }}" alt="{{ $productToEdit?->name }}">
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
                        <p class="admin-product-optional-copy">Upload a product image now, or leave it empty and add one later.</p>
                    </div>
                </details>

                <div class="admin-product-actions">
                    <p>Marked price is optional. If provided, it must be greater than or equal to the actual selling price.</p>
                    <button type="submit" class="admin-primary-pill">{{ $isEditingProduct ? 'Update Product' : 'Save Product' }}</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
