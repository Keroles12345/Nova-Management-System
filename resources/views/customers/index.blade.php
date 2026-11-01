@extends('layouts.app')

@section('title', 'Customers')
@section('breadcrumb', 'Customers')

@section('content')
<div class="page-heading">
    <div><h1>Customers</h1><p>Search and manage customers linked to your orders.</p></div>
    <a class="button" href="{{ route('customers.create') }}">＋ Create customer</a>
</div>

<form class="card filter-bar" method="GET" action="{{ route('customers.index') }}" style="padding:13px 16px;margin-bottom:15px">
    <label class="filter-field">Search
        <input type="search" name="q" value="{{ $search }}" placeholder="Customer name or email">
    </label>
    <button class="button small" type="submit">Search</button>
    <a class="button soft small" href="{{ route('customers.index') }}">Reset</a>
</form>

<section class="card">
    <div class="card-heading">
        <div><h2>Customer list</h2><p>{{ number_format($customers->total()) }} matching customers</p></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Customer</th><th>Email</th><th>Orders</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td class="table-primary">{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ number_format($customer->orders_count) }}</td>
                        <td>
                            <div class="product-actions">
                                <button
                                    class="button soft small"
                                    type="button"
                                    data-edit-customer
                                    data-id="{{ $customer->id }}"
                                    data-name="{{ $customer->name }}"
                                    data-email="{{ $customer->email }}"
                                    data-update-url="{{ route('customers.update', $customer) }}"
                                >Edit</button>
                                @if ($customer->orders_count === 0)
                                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                                        @csrf @method('DELETE')
                                        <button class="button danger small" type="submit">Delete</button>
                                    </form>
                                @else
                                    <button class="button danger small" type="button" disabled title="Customers with order history cannot be deleted">Has orders</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No customers match your search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $customers->links() }}</div>
</section>

<dialog class="order-dialog" id="edit-customer-dialog" aria-labelledby="edit-customer-title">
    <section class="card order-dialog-panel">
        <div class="card-heading">
            <div><h2 id="edit-customer-title">Edit customer</h2><p>Update the customer's contact details.</p></div>
            <button class="button soft small" type="button" data-close-customer-dialog aria-label="Close dialog">Close</button>
        </div>
        <form class="product-form" id="edit-customer-form" method="POST">
            @csrf @method('PATCH')
            <input type="hidden" name="customer_id" id="edit-customer-id">
            <label for="edit-customer-name">Customer name
                <input id="edit-customer-name" name="name" maxlength="255" autocomplete="name" required>
            </label>
            <label for="edit-customer-email">Email address
                <input id="edit-customer-email" type="email" name="email" maxlength="255" autocomplete="email" required>
            </label>
            <div class="product-form-actions">
                <button class="button soft" type="button" data-close-customer-dialog>Cancel</button>
                <button class="button" type="submit">Save changes</button>
            </div>
        </form>
    </section>
</dialog>

<script>
    const editCustomerDialog = document.getElementById('edit-customer-dialog');

    document.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-edit-customer]');
        if (editButton) {
            document.getElementById('edit-customer-form').action = editButton.dataset.updateUrl;
            document.getElementById('edit-customer-id').value = editButton.dataset.id;
            document.getElementById('edit-customer-name').value = editButton.dataset.name;
            document.getElementById('edit-customer-email').value = editButton.dataset.email;
            editCustomerDialog.showModal();
        }

        if (event.target.closest('[data-close-customer-dialog]') || event.target === editCustomerDialog) {
            editCustomerDialog.close();
        }
    });

    @if ($errors->hasAny(['name', 'email']) && old('customer_id'))
        const editButton = document.querySelector(`[data-edit-customer][data-id="${CSS.escape(@js((string) old('customer_id')))}"]`);
        if (editButton) {
            editButton.click();
            document.getElementById('edit-customer-name').value = @js(old('name'));
            document.getElementById('edit-customer-email').value = @js(old('email'));
        }
    @endif
</script>
@endsection
