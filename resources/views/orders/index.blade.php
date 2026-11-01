@extends('layouts.app')

@section('title', 'Order management')
@section('breadcrumb', 'Orders')

@section('content')
<div class="page-heading">
    <div><h1>Order management</h1><p>Create orders, review details, edit items, and cancel orders.</p></div>
    <button class="button" type="button" data-open-order-dialog>＋ Create order</button>
</div>

<form class="card filter-bar" method="GET" action="{{ route('orders.index') }}" style="padding:13px 16px;margin-bottom:15px">
    <label class="filter-field">Search
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Order ID, customer, product">
    </label>
    <label class="filter-field">From <input type="date" name="from" value="{{ $filters['from'] }}"></label>
    <label class="filter-field">To <input type="date" name="to" value="{{ $filters['to'] }}"></label>
    <label class="filter-field">Status
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <button class="button small" type="submit">Apply filters</button>
    <a class="button soft small" href="{{ route('orders.index') }}">Reset</a>
</form>

<section class="card">
    <div class="card-heading"><div><h2>Orders</h2><p>{{ number_format($orders->total()) }} matching orders</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="table-primary">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $order->customer?->name ?? 'Customer' }}<span class="table-sub">{{ $order->customer?->email }}</span></td>
                        <td>
                            <details class="order-details">
                                <summary class="button soft small">View {{ $order->items->count() }} items</summary>
                                <div class="order-items">
                                    @foreach ($order->items as $item)
                                        <div><span>{{ $item->product?->name ?? 'Product' }} <small>{{ $item->product?->sku }}</small></span><strong>{{ $item->quantity }} × {{ number_format((float) $item->unit_price, 2) }} EGP</strong></div>
                                    @endforeach
                                </div>
                            </details>
                        </td>
                        <td>{{ $order->ordered_at->format('d/m/Y H:i') }}</td>
                        <td class="table-primary">{{ number_format((float) $order->total, 2) }} EGP</td>
                        <td><span class="badge {{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                        <td><div class="order-action-buttons">
                            <button
                                class="button soft small"
                                type="button"
                                data-edit-order
                                data-id="{{ $order->id }}"
                                data-update-url="{{ route('orders.update', $order) }}"
                                data-customer-id="{{ $order->customer_id }}"
                                data-status="{{ $order->status }}"
                                data-ordered-at="{{ $order->ordered_at->format('Y-m-d\TH:i') }}"
                                data-items="{{ $order->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity])->values()->toJson() }}"
                            >Edit</button>
                            @if ($order->status !== 'cancelled')
                                <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Cancel this order? The order record will be kept.')">
                                    @csrf @method('DELETE')
                                    <button class="button danger small" type="submit">Delete</button>
                                </form>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $orders->links() }}</div>
</section>

<dialog class="order-dialog" id="edit-order-dialog" aria-labelledby="edit-order-title">
    <section class="card order-dialog-panel">
        <div class="card-heading">
            <div><h2 id="edit-order-title">Edit order</h2><p>Update the customer, status, date, and products.</p></div>
            <button class="button soft small" type="button" data-close-order-dialog aria-label="Close dialog">Close</button>
        </div>
        <form class="order-create-form" id="edit-order-form" method="POST">
            @csrf @method('PATCH')
            <input type="hidden" name="order_id" id="edit-order-id">
            <div class="order-create-fields">
                <label>Customer
                    <select name="customer_id" id="edit-order-customer" required>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Status
                    <select name="status" id="edit-order-status" required>
                        @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Order date
                    <input type="datetime-local" name="ordered_at" id="edit-order-date" required>
                </label>
            </div>
            <div class="order-edit-items" id="edit-order-items" data-order-items data-next-index="0"></div>
            <div class="order-create-actions">
                <button class="button soft small" type="button" data-add-item>Add product</button>
                <button class="button" type="submit">Save changes</button>
            </div>
        </form>
    </section>
</dialog>

<dialog class="order-dialog" id="create-order-dialog" aria-labelledby="create-order-title">
    <section class="card order-dialog-panel">
    <div class="card-heading"><div><h2 id="create-order-title">Create order</h2><p>Choose an existing customer or add a new one. Total uses current product prices.</p></div><button class="button soft small" type="button" data-close-order-dialog aria-label="Close dialog">Close</button></div>
    @if ($products->isEmpty())
        <div class="empty order-prerequisite">
            <strong>No products available yet</strong>
            <span>Create the first product, then return here to add it to an order.</span>
            <a class="button" href="{{ route('products.index') }}">＋ Create product</a>
        </div>
    @else
        <form class="order-create-form" method="POST" action="{{ route('orders.store') }}">
            @csrf
            @if ($customers->isNotEmpty())
                <fieldset class="customer-mode">
                    <legend>Customer</legend>
                    <label><input type="radio" name="customer_mode" value="existing" @checked(old('customer_mode', 'existing') === 'existing')> Choose existing customer</label>
                    <label><input type="radio" name="customer_mode" value="new" @checked(old('customer_mode') === 'new')> Add a new customer</label>
                </fieldset>
            @else
                <input type="hidden" name="customer_mode" value="new">
                <p class="form-hint">No customers yet. Add the customer details below to create your first order.</p>
            @endif
            <div class="order-create-fields" data-existing-customer-fields @if ($customers->isEmpty() || old('customer_mode') === 'new') hidden @endif>
                <label>Customer
                    <select name="customer_id" @required($customers->isNotEmpty() && old('customer_mode', 'existing') !== 'new') @disabled($customers->isEmpty() || old('customer_mode') === 'new')>
                        <option value="">Select a customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} ({{ $customer->email }})</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="order-create-fields" data-new-customer-fields @if ($customers->isNotEmpty() && old('customer_mode', 'existing') !== 'new') hidden @endif>
                <label>Customer name
                    <input name="new_customer_name" value="{{ old('new_customer_name') }}" maxlength="255" @required($customers->isEmpty() || old('customer_mode') === 'new') @disabled($customers->isNotEmpty() && old('customer_mode', 'existing') !== 'new')>
                </label>
                <label>Customer email
                    <input type="email" name="new_customer_email" value="{{ old('new_customer_email') }}" maxlength="255" @required($customers->isEmpty() || old('customer_mode') === 'new') @disabled($customers->isNotEmpty() && old('customer_mode', 'existing') !== 'new')>
                </label>
            </div>
            <div class="order-create-fields">
                <label>Status
                    <select name="status" required>
                        @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'pending') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Order date <input type="datetime-local" name="ordered_at" value="{{ old('ordered_at', now()->format('Y-m-d\TH:i')) }}" required></label>
            </div>
            <div class="order-edit-items" data-order-items data-next-index="1">
                <div class="order-edit-item" data-order-item>
                    <select name="items[0][product_id]" required aria-label="Product">
                        <option value="">Select a product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} — {{ number_format((float) $product->price, 2) }} EGP</option>
                        @endforeach
                    </select>
                    <input type="number" name="items[0][quantity]" value="1" min="1" required aria-label="Quantity">
                    <button class="button danger small" type="button" data-remove-item aria-label="Remove item">×</button>
                </div>
            </div>
            <div class="order-create-actions">
                <button class="button soft small" type="button" data-add-item>Add product</button>
                <button class="button" type="submit">Create order</button>
            </div>
        </form>
    @endif
    </section>
</dialog>

<template id="order-item-template">
    <div class="order-edit-item" data-order-item>
        <select data-product-select required aria-label="Product">
            <option value="">Select a product</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }} — {{ number_format((float) $product->price, 2) }} EGP</option>
            @endforeach
        </select>
        <input data-quantity-input type="number" value="1" min="1" required aria-label="Quantity">
        <button class="button danger small" type="button" data-remove-item aria-label="Remove item">×</button>
    </div>
</template>

<script>
    const createOrderDialog = document.getElementById('create-order-dialog');
    const editOrderDialog = document.getElementById('edit-order-dialog');

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-open-order-dialog]')) {
            createOrderDialog.showModal();
        }

        if (event.target.closest('[data-close-order-dialog]')) {
            if (createOrderDialog.open) {
                createOrderDialog.close();
            }
            if (editOrderDialog.open) {
                editOrderDialog.close();
            }
        }

        if (event.target === createOrderDialog || event.target === editOrderDialog) {
            createOrderDialog.close();
            editOrderDialog.close();
        }

        const editButton = event.target.closest('[data-edit-order]');
        if (editButton) {
            const form = document.getElementById('edit-order-form');
            const items = document.getElementById('edit-order-items');
            form.action = editButton.dataset.updateUrl;
            document.getElementById('edit-order-id').value = editButton.dataset.id;
            document.getElementById('edit-order-customer').value = editButton.dataset.customerId;
            document.getElementById('edit-order-status').value = editButton.dataset.status;
            document.getElementById('edit-order-date').value = editButton.dataset.orderedAt;
            items.replaceChildren();
            items.dataset.nextIndex = '0';
            JSON.parse(editButton.dataset.items).forEach((item) => {
                appendOrderItem(form, item);
            });
            editOrderDialog.showModal();
        }

        const addButton = event.target.closest('[data-add-item]');
        if (addButton) {
            const form = addButton.closest('form');
            appendOrderItem(form);
        }

        const removeButton = event.target.closest('[data-remove-item]');
        if (removeButton) {
            const items = removeButton.closest('[data-order-items]');
            if (items.querySelectorAll('[data-order-item]').length > 1) {
                removeButton.closest('[data-order-item]').remove();
            }
        }
    });

    function appendOrderItem(form, item = null) {
        const items = form.querySelector('[data-order-items]');
        const template = document.getElementById('order-item-template');
        const row = template.content.firstElementChild.cloneNode(true);
        const index = Number(items.dataset.nextIndex);
        items.dataset.nextIndex = String(index + 1);
        const productSelect = row.querySelector('[data-product-select]');
        productSelect.name = `items[${index}][product_id]`;
        row.querySelector('[data-quantity-input]').name = `items[${index}][quantity]`;

        if (item) {
            productSelect.value = String(item.product_id);
            row.querySelector('[data-quantity-input]').value = item.quantity;
        }

        items.append(row);
    }

    document.addEventListener('change', (event) => {
        if (event.target.name !== 'customer_mode') {
            return;
        }

        const form = event.target.form;
        const isNewCustomer = event.target.value === 'new';
        const existingFields = form.querySelector('[data-existing-customer-fields]');
        const newFields = form.querySelector('[data-new-customer-fields]');
        existingFields.hidden = isNewCustomer;
        newFields.hidden = !isNewCustomer;

        existingFields.querySelectorAll('select, input').forEach((input) => {
            input.disabled = isNewCustomer;
            input.required = !isNewCustomer;
        });
        newFields.querySelectorAll('input').forEach((input) => {
            input.disabled = !isNewCustomer;
            input.required = isNewCustomer;
        });
    });

    @if (! old('order_id') && ($errors->hasAny(['customer_id', 'customer_mode', 'new_customer_name', 'new_customer_email', 'ordered_at']) || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'items.'))))
        createOrderDialog.showModal();
    @endif

    @if (old('order_id') && ($errors->hasAny(['customer_id', 'status', 'ordered_at']) || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'items.'))))
        const failedOrderButton = document.querySelector(`[data-edit-order][data-id="${CSS.escape(@js((string) old('order_id')))}"]`);
        if (failedOrderButton) {
            failedOrderButton.click();
            document.getElementById('edit-order-customer').value = @js((string) old('customer_id'));
            document.getElementById('edit-order-status').value = @js(old('status'));
            document.getElementById('edit-order-date').value = @js(old('ordered_at'));
        }
    @endif
</script>
@endsection
