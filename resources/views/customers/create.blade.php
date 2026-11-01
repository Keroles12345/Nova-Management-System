@extends('layouts.app')

@section('title', 'Create customer')
@section('breadcrumb', 'Customers / Create')

@section('content')
<div class="page-heading">
    <div><h1>Create customer</h1><p>Add a customer to use when creating orders.</p></div>
    <a class="button soft" href="{{ route('customers.index') }}">Back to customers</a>
</div>

<section class="card">
    <div class="card-heading">
        <div><h2>Customer details</h2><p>Enter the customer's name and email address.</p></div>
    </div>
    <form class="product-form" method="POST" action="{{ route('customers.store') }}">
        @csrf
        <label for="customer-name">Customer name
            <input id="customer-name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>
        </label>
        <label for="customer-email">Email address
            <input id="customer-email" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
        </label>
        <div class="product-form-actions">
            <a class="button soft" href="{{ route('customers.index') }}">Cancel</a>
            <button class="button" type="submit">Create customer</button>
        </div>
    </form>
</section>
@endsection
