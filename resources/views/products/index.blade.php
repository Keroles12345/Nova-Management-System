@extends('layouts.app')

@section('title', 'Products')
@section('breadcrumb', 'Products')

@section('content')
<div class="page-heading">
    <div><h1>Products</h1><p>Create and manage your product catalog.</p></div>
    <button class="button" type="button" data-open-product-dialog>＋ Create product</button>
</div>

<form class="card filter-bar" method="GET" action="{{ route('products.index') }}" style="padding:13px 16px;margin-bottom:15px">
    <label class="filter-field">Search
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Name, SKU, or category">
    </label>
    <label class="filter-field">Category
        <select name="category">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </label>
    <label class="filter-field">Min price <input type="number" name="min_price" min="0" step="0.01" value="{{ $filters['min_price'] }}" placeholder="0.00"></label>
    <label class="filter-field">Max price <input type="number" name="max_price" min="0" step="0.01" value="{{ $filters['max_price'] }}" placeholder="Any"></label>
    <button class="button small" type="submit">Apply filters</button>
    <a class="button soft small" href="{{ route('products.index') }}">Reset</a>
</form>

<section class="card">
    <div class="card-heading"><div><h2>Product catalog</h2><p>{{ number_format($products->total()) }} matching products</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Order items</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="table-primary">{{ $product->name }}</td>
                        <td>{{ $product->sku }}</td>
                        <td><span class="badge processing">{{ $product->category }}</span></td>
                        <td class="table-primary">{{ number_format((float) $product->price, 2) }} EGP</td>
                        <td>{{ number_format($product->order_items_count) }}</td>
                        <td>
                            <div class="product-actions">
                                <button
                                    class="button soft small"
                                    type="button"
                                    data-edit-product
                                    data-id="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-sku="{{ $product->sku }}"
                                    data-category="{{ $product->category }}"
                                    data-price="{{ $product->price }}"
                                    data-update-url="{{ route('products.update', $product) }}"
                                >Edit</button>
                                @if ($product->order_items_count === 0)
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                        @csrf @method('DELETE')
                                        <button class="button danger small" type="submit">Delete</button>
                                    </form>
                                @else
                                    <button class="button danger small" type="button" disabled title="Products used in order history cannot be deleted">In orders</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No products match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $products->links() }}</div>
</section>

<dialog class="order-dialog" id="create-product-dialog" aria-labelledby="create-product-title">
    <section class="card order-dialog-panel">
        <div class="card-heading">
            <div><h2 id="create-product-title">Create product</h2><p>Add a product to your catalog.</p></div>
            <button class="button soft small" type="button" data-close-product-dialog aria-label="Close dialog">Close</button>
        </div>
        <form class="product-form" method="POST" action="{{ route('products.store') }}">
            @csrf
            <label>Product name
                <input name="name" value="{{ old('name') }}" maxlength="255" required>
            </label>
            <label>SKU
                <input name="sku" value="{{ old('sku') }}" maxlength="255" required>
            </label>
            <label>Category
                <input name="category" value="{{ old('category') }}" maxlength="255" required>
            </label>
            <label>Price (EGP)
                <input type="number" name="price" value="{{ old('price') }}" min="0" step="0.01" required>
            </label>
            <div class="product-form-actions">
                <button class="button soft" type="button" data-close-product-dialog>Cancel</button>
                <button class="button" type="submit">Create product</button>
            </div>
        </form>
    </section>
</dialog>

<dialog class="order-dialog" id="edit-product-dialog" aria-labelledby="edit-product-title">
    <section class="card order-dialog-panel">
        <div class="card-heading">
            <div><h2 id="edit-product-title">Edit product</h2><p>Update product details.</p></div>
            <button class="button soft small" type="button" data-close-product-dialog aria-label="Close dialog">Close</button>
        </div>
        <form class="product-form" id="edit-product-form" method="POST">
            @csrf @method('PATCH')
            <input type="hidden" name="product_id" id="edit-product-id">
            <label>Product name
                <input name="name" id="edit-product-name" maxlength="255" required>
            </label>
            <label>SKU
                <input name="sku" id="edit-product-sku" maxlength="255" required>
            </label>
            <label>Category
                <input name="category" id="edit-product-category" maxlength="255" required>
            </label>
            <label>Price (EGP)
                <input type="number" name="price" id="edit-product-price" min="0" step="0.01" required>
            </label>
            <div class="product-form-actions">
                <button class="button soft" type="button" data-close-product-dialog>Cancel</button>
                <button class="button" type="submit">Save changes</button>
            </div>
        </form>
    </section>
</dialog>

<script>
    const createProductDialog = document.getElementById('create-product-dialog');
    const editProductDialog = document.getElementById('edit-product-dialog');

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-open-product-dialog]')) {
            createProductDialog.showModal();
        }

        const editButton = event.target.closest('[data-edit-product]');
        if (editButton) {
            document.getElementById('edit-product-form').action = editButton.dataset.updateUrl;
            document.getElementById('edit-product-id').value = editButton.dataset.id;
            document.getElementById('edit-product-name').value = editButton.dataset.name;
            document.getElementById('edit-product-sku').value = editButton.dataset.sku;
            document.getElementById('edit-product-category').value = editButton.dataset.category;
            document.getElementById('edit-product-price').value = editButton.dataset.price;
            editProductDialog.showModal();
        }

        if (event.target.closest('[data-close-product-dialog]')) {
            createProductDialog.close();
            editProductDialog.close();
        }

        if (event.target === createProductDialog) {
            createProductDialog.close();
        }

        if (event.target === editProductDialog) {
            editProductDialog.close();
        }
    });

    @if ($errors->hasAny(['name', 'sku', 'category', 'price']) && old('product_id'))
        const editButton = document.querySelector(`[data-edit-product][data-id="${CSS.escape(@js((string) old('product_id')))}"]`);
        if (editButton) {
            editButton.click();
            document.getElementById('edit-product-name').value = @js(old('name'));
            document.getElementById('edit-product-sku').value = @js(old('sku'));
            document.getElementById('edit-product-category').value = @js(old('category'));
            document.getElementById('edit-product-price').value = @js(old('price'));
        }
    @elseif ($errors->hasAny(['name', 'sku', 'category', 'price']))
        createProductDialog.showModal();
    @endif
</script>
@endsection
