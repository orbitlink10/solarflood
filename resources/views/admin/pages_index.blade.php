@extends('admin.layout')

@section('content')
<div class="admin-shell">
    @include('admin.partials.sidebar', ['activeAdminNav' => 'pages'])

    <div class="admin-main admin-management-main">
        <section class="admin-page-head">
            <div>
                <h1 class="admin-page-title">Pages</h1>
                <p class="admin-page-copy">Manage site pages and published content.</p>
            </div>
        </section>

        <section class="panel admin-list-panel">
            <div class="admin-list-panel-head">
                <h2>Post List</h2>
                <a
                    class="admin-secondary-pill @unless($pagesStorageReady) is-disabled @endunless"
                    href="{{ $pagesStorageReady ? route('admin.pages.create') : '#' }}"
                    @unless($pagesStorageReady) aria-disabled="true" @endunless
                >+ Add Page</a>
            </div>

            @unless($pagesStorageReady)
                <div class="alert error">
                    Page storage is not ready yet. Run <code>php artisan migrate</code> to create the <code>pages</code> table before managing pages.
                </div>
            @endunless

            <div class="admin-bulk-row">
                <select>
                    <option>Bulk actions</option>
                </select>
                <button type="button" class="admin-filter-button">Apply</button>
            </div>

            <div class="table-wrap">
                <table class="admin-data-table">
                    <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>No.</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Alt Text</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td><input type="checkbox"></td>
                            <td>{{ $pages->firstItem() + $loop->index }}</td>
                            <td>
                                @if($page->image_url)
                                    <img class="admin-thumb admin-thumb--page" src="{{ $page->image_url }}" alt="{{ $page->alt_text ?: $page->title }}">
                                @else
                                    <div class="admin-thumb admin-thumb--placeholder admin-thumb--page">No Image</div>
                                @endif
                            </td>
                            <td>{{ $page->title }}</td>
                            <td>{{ $page->alt_text ?: 'N/A' }}</td>
                            <td>{{ ucfirst($page->type) }}</td>
                            <td>
                                <div class="admin-action-stack">
                                    <a
                                        class="admin-outline-action tone-info"
                                        href="{{ route('pages.show', ['page' => $page->slug]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >Preview</a>
                                    <a
                                        class="admin-outline-action tone-warning"
                                        href="{{ route('admin.pages.edit', $page) }}"
                                    >Update</a>
                                    <form method="post" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-outline-action tone-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-empty-cell">No pages published yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($pages->hasPages())
                <div class="pager">
                    @if($pages->onFirstPage())
                        <span class="pager-link disabled">Previous</span>
                    @else
                        <a class="pager-link" href="{{ $pages->previousPageUrl() }}">Previous</a>
                    @endif
                    <span>Page {{ $pages->currentPage() }} of {{ $pages->lastPage() }}</span>
                    @if($pages->hasMorePages())
                        <a class="pager-link" href="{{ $pages->nextPageUrl() }}">Next</a>
                    @else
                        <span class="pager-link disabled">Next</span>
                    @endif
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
