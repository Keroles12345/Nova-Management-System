<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_seeder_creates_demo_roles_tasks_and_order_analytics(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@example.test', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'manager@example.test', 'role' => 'manager']);
        $this->assertDatabaseHas('tasks', ['title' => 'Review weekly orders']);
        $this->assertDatabaseCount('orders', 40);
        $this->assertDatabaseCount('products', 5);
    }

    public function test_local_seeder_fills_missing_demo_data_without_duplicating_or_removing_existing_records(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Existing Customer', 'email' => 'existing@example.test']);
        $product = Product::create([
            'name' => 'Existing Product',
            'sku' => 'EXISTING-1',
            'category' => 'Test',
            'price' => 80,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 80,
            'ordered_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 80,
        ]);
        Task::create([
            'user_id' => $user->id,
            'title' => 'Existing task',
            'status' => 'pending',
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('tasks', 5);
        $this->assertDatabaseCount('products', 6);
        $this->assertDatabaseCount('customers', 13);
        $this->assertDatabaseCount('orders', 13);
        $this->assertDatabaseHas('orders', ['status' => 'cancelled']);
        $this->assertModelExists($order->fresh());
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_manager_sees_filtered_order_analytics_and_best_selling_products(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::create(['name' => 'Customer One', 'email' => 'customer@example.test']);
        $product = Product::create([
            'name' => 'Wireless Headphones',
            'sku' => 'HEAD-1',
            'category' => 'Audio',
            'price' => 1250,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 2500,
            'ordered_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1250,
        ]);

        $this->actingAs($manager)->get('/dashboard')
            ->assertOk()
            ->assertSee('Wireless Headphones')
            ->assertSee('Customer One')
            ->assertSee('2,500');
    }

    public function test_manager_can_search_orders_review_details_and_update_status(): void
    {
        $this->travelTo('2026-06-15 12:00:00');
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::create(['name' => 'Searchable Customer', 'email' => 'search@example.test']);
        $otherCustomer = Customer::create(['name' => 'Other Customer', 'email' => 'other@example.test']);
        $product = Product::create([
            'name' => 'Wireless Headphones',
            'sku' => 'HEAD-2',
            'category' => 'Audio',
            'price' => 1250,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 2500,
            'ordered_at' => now()->subDays(2),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1250,
        ]);
        Order::create([
            'customer_id' => $otherCustomer->id,
            'status' => 'cancelled',
            'total' => 50,
            'ordered_at' => now()->subDay(),
        ]);

        $this->actingAs($manager)->get('/orders?from=2026-06-01&to=2026-06-15&status=completed&q=Wireless')
            ->assertOk()
            ->assertSee('Searchable Customer')
            ->assertSee('Wireless Headphones')
            ->assertSee('2 × 1,250.00 EGP')
            ->assertSee('#00001')
            ->assertDontSee('#00002')
            ->assertSee('data-open-order-dialog', false)
            ->assertSee('data-edit-order', false)
            ->assertSee('id="edit-order-dialog"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('<dialog class="order-dialog" id="create-order-dialog"', false)
            ->assertSee('data-close-order-dialog', false);

        $this->actingAs($manager)->patch("/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_manager_can_create_edit_and_cancel_order_without_deleting_its_history(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::create(['name' => 'Order Customer', 'email' => 'order@example.test']);
        $updatedCustomer = Customer::create(['name' => 'Updated Customer', 'email' => 'updated@example.test']);
        $product = Product::create([
            'name' => 'Original Product',
            'sku' => 'ORIGINAL-1',
            'category' => 'Test',
            'price' => 100,
        ]);
        $updatedProduct = Product::create([
            'name' => 'Updated Product',
            'sku' => 'UPDATED-1',
            'category' => 'Test',
            'price' => 250,
        ]);

        $this->actingAs($manager)->post('/orders', [
            'customer_mode' => 'existing',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'ordered_at' => '2026-06-15T12:00',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 200,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
        ]);

        $this->actingAs($manager)->patch("/orders/{$order->id}", [
            'customer_id' => $updatedCustomer->id,
            'status' => 'processing',
            'ordered_at' => '2026-06-16T13:30',
            'items' => [
                ['product_id' => $updatedProduct->id, 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $updatedCustomer->id,
            'status' => 'processing',
            'total' => 750,
        ]);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $updatedProduct->id,
            'quantity' => 3,
            'unit_price' => 250,
        ]);

        $this->actingAs($manager)->from('/orders')->patch("/orders/{$order->id}", [
            'customer_id' => $updatedCustomer->id,
            'status' => 'not-a-status',
            'ordered_at' => '2026-06-16T13:30',
            'order_id' => $order->id,
            'items' => [
                ['product_id' => $updatedProduct->id, 'quantity' => 3],
            ],
        ])->assertRedirect('/orders')
            ->assertSessionHasErrors(['status'])
            ->assertSessionHasInput('order_id', $order->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
            'total' => 750,
        ]);

        $this->actingAs($manager)->delete("/orders/{$order->id}")
            ->assertRedirect();

        $this->assertModelExists($order->fresh());
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $updatedProduct->id,
            'quantity' => 3,
        ]);
    }

    public function test_manager_can_create_order_with_a_new_customer_and_is_guided_to_create_a_product(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get('/orders')
            ->assertOk()
            ->assertSee('No products available yet')
            ->assertSee('Create product')
            ->assertSee(route('products.index'));

        $product = Product::create([
            'name' => 'First Product',
            'sku' => 'FIRST-1',
            'category' => 'Test',
            'price' => 75,
        ]);

        $this->actingAs($manager)->get('/orders')
            ->assertOk()
            ->assertSee('name="new_customer_name"', false)
            ->assertSee('name="new_customer_email"', false);

        $this->actingAs($manager)->post('/orders', [
            'customer_mode' => 'new',
            'new_customer_name' => 'First Customer',
            'new_customer_email' => 'first@example.test',
            'status' => 'pending',
            'ordered_at' => '2026-06-15T12:00',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $customer = Customer::query()->where('email', 'first@example.test')->firstOrFail();
        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'First Customer',
            'email' => 'first@example.test',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'total' => 150,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 75,
        ]);

        $this->actingAs($manager)->from('/orders')->post('/orders', [
            'customer_mode' => 'new',
            'new_customer_name' => 'Duplicate Customer',
            'new_customer_email' => 'first@example.test',
            'status' => 'pending',
            'ordered_at' => '2026-06-15T12:00',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect('/orders')
            ->assertSessionHasErrors(['new_customer_email']);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_manager_can_create_customer_and_duplicate_email_is_rejected(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get('/customers/create')
            ->assertOk()
            ->assertSee('Create customer')
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false);

        $this->actingAs($manager)->post('/customers', [
            'name' => 'Page Customer',
            'email' => 'page-customer@example.test',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Page Customer',
            'email' => 'page-customer@example.test',
        ]);

        $this->actingAs($manager)->from('/customers/create')->post('/customers', [
            'name' => 'Duplicate Customer',
            'email' => 'page-customer@example.test',
        ])->assertRedirect('/customers/create')
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_manager_can_search_edit_and_delete_customers_without_order_history(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::create([
            'name' => 'Searchable Customer',
            'email' => 'searchable@example.test',
        ]);
        Customer::create([
            'name' => 'Other Customer',
            'email' => 'other@example.test',
        ]);

        $this->actingAs($manager)->get('/customers?q=searchable')
            ->assertOk()
            ->assertSee('Searchable Customer')
            ->assertSee('searchable@example.test')
            ->assertDontSee('Other Customer')
            ->assertSee('data-edit-customer', false);

        $this->actingAs($manager)->patch("/customers/{$customer->id}", [
            'name' => 'Updated Customer',
            'email' => 'searchable@example.test',
            'customer_id' => $customer->id,
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
            'email' => 'searchable@example.test',
        ]);

        $this->actingAs($manager)->from('/customers')->patch("/customers/{$customer->id}", [
            'name' => 'Updated Customer',
            'email' => 'other@example.test',
            'customer_id' => $customer->id,
        ])->assertRedirect('/customers')
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'email' => 'searchable@example.test',
        ]);

        $this->actingAs($manager)->delete("/customers/{$customer->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_with_order_history_cannot_be_deleted(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::create([
            'name' => 'Order History Customer',
            'email' => 'history@example.test',
        ]);
        Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 100,
            'ordered_at' => now(),
        ]);

        $this->actingAs($manager)->delete("/customers/{$customer->id}")
            ->assertRedirect()
            ->assertSessionHasErrors('customer');

        $this->assertModelExists($customer);
    }

    public function test_regular_user_cannot_access_customer_creation_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/customers')->assertForbidden();
    }

    public function test_dashboard_displays_orders_chart_monthly_revenue_and_sales_comparison(): void
    {
        $this->travelTo('2026-06-15 12:00:00');
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::create(['name' => 'Analytics Customer', 'email' => 'analytics@example.test']);

        Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 300,
            'ordered_at' => now(),
        ]);
        Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 100,
            'ordered_at' => now()->subDays(7),
        ]);

        $this->actingAs($manager)->get('/dashboard')
            ->assertOk()
            ->assertSee('Orders chart')
            ->assertSee('Monthly revenue')
            ->assertSee('Sales comparison')
            ->assertSee('400 EGP')
            ->assertSee('200.0% increase');
    }

    public function test_user_role_cannot_manage_orders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/orders')->assertForbidden();
    }

    public function test_manager_can_create_search_update_and_delete_products(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->post('/products', [
            'name' => 'Desk Lamp',
            'sku' => 'LAMP-001',
            'category' => 'Home',
            'price' => '49.95',
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'LAMP-001')->firstOrFail();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Desk Lamp',
            'category' => 'Home',
            'price' => '49.95',
        ]);

        $this->actingAs($manager)->get('/products?q=LAMP-001&category=Home&min_price=40&max_price=60')
            ->assertOk()
            ->assertSee('Desk Lamp')
            ->assertSee('LAMP-001')
            ->assertSee('Create product')
            ->assertSee('Edit')
            ->assertSee('data-open-product-dialog', false);

        $this->actingAs($manager)->patch("/products/{$product->id}", [
            'name' => 'Updated Desk Lamp',
            'sku' => 'LAMP-002',
            'category' => 'Lighting',
            'price' => '59.50',
            'product_id' => $product->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Desk Lamp',
            'sku' => 'LAMP-002',
            'category' => 'Lighting',
            'price' => '59.50',
        ]);

        $this->actingAs($manager)->delete("/products/{$product->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_with_order_history_cannot_be_deleted(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::create(['name' => 'Product Customer', 'email' => 'product-customer@example.test']);
        $product = Product::create([
            'name' => 'Ordered Product',
            'sku' => 'ORDERED-1',
            'category' => 'Test',
            'price' => 80,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 80,
            'ordered_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 80,
        ]);

        $this->actingAs($manager)->delete("/products/{$product->id}")
            ->assertRedirect()
            ->assertSessionHasErrors('product');

        $this->assertModelExists($product);
    }

    public function test_regular_user_cannot_manage_products(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/products')->assertForbidden();
    }

    public function test_order_status_update_rejects_unknown_status_and_keeps_existing_status(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::create(['name' => 'Customer', 'email' => 'customer-status@example.test']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 100,
            'ordered_at' => now(),
        ]);

        $this->actingAs($manager)->patch("/orders/{$order->id}/status", ['status' => 'unknown'])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_user_role_cannot_open_admin_user_management(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }
}
