<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Store Administrator', 'password' => Hash::make('password'), 'role' => 'admin'],
        );
        $manager = User::firstOrCreate(
            ['email' => 'manager@example.test'],
            ['name' => 'Operations Manager', 'password' => Hash::make('password'), 'role' => 'manager'],
        );
        $member = User::firstOrCreate(
            ['email' => 'user@example.test'],
            ['name' => 'Demo User', 'password' => Hash::make('password'), 'role' => 'user'],
        );

        $tasks = [
            [$member, 'Review weekly orders', 'Review new orders and confirm they are ready to ship.', 'in_progress', today()->addDays(2)],
            [$member, 'Refresh product photography', 'Upload updated photos for the seasonal collection.', 'pending', today()->addDays(4)],
            [$member, 'Prepare the sales report', 'Share a summary of store performance with the team.', 'completed', today()->subDay()],
            [$manager, 'Follow up on supplier shipment', null, 'pending', today()->addDay()],
        ];

        foreach ($tasks as [$owner, $title, $description, $status, $dueDate]) {
            $owner->tasks()->firstOrCreate(
                ['title' => $title],
                ['description' => $description, 'status' => $status, 'due_date' => $dueDate],
            );
        }

        $catalog = [
            ['name' => 'Studio Pro Headphones', 'sku' => 'AUD-001', 'category' => 'Electronics', 'price' => 2899],
            ['name' => 'Active One Watch', 'sku' => 'WAT-014', 'category' => 'Accessories', 'price' => 1749],
            ['name' => 'Urban Pack Backpack', 'sku' => 'BAG-006', 'category' => 'Bags', 'price' => 1299],
            ['name' => 'Mini 4K Camera', 'sku' => 'CAM-022', 'category' => 'Electronics', 'price' => 3599],
            ['name' => 'Aura Desk Lamp', 'sku' => 'HOM-018', 'category' => 'Home', 'price' => 899],
        ];

        foreach ($catalog as $item) {
            Product::firstOrCreate(['sku' => $item['sku']], $item);
        }

        $customers = collect(range(1, 12))->map(fn (int $number) => Customer::firstOrCreate(
            ['email' => "customer{$number}@example.test"],
            ['name' => fake()->name()],
        ));
        $products = Product::query()->orderBy('id')->get();

        if (Order::query()->doesntExist()) {
            $statuses = ['completed', 'completed', 'processing', 'pending', 'cancelled'];

            foreach (range(0, 39) as $index) {
                $product = $products[$index % $products->count()];
                $quantity = ($index % 3) + 1;
                $order = Order::create([
                    'customer_id' => $customers[$index % $customers->count()]->id,
                    'status' => $statuses[$index % count($statuses)],
                    'total' => $quantity * $product->price,
                    'ordered_at' => now()->subDays($index % 35)->subHours($index % 8),
                ]);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ]);
            }
        } else {
            foreach ($customers as $index => $customer) {
                if ($customer->orders()->exists()) {
                    continue;
                }

                $product = $products[$index % $products->count()];
                $quantity = ($index % 3) + 1;
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'status' => ['completed', 'processing', 'pending', 'cancelled'][$index % 4],
                    'total' => $quantity * $product->price,
                    'ordered_at' => now()->subDays($index),
                ]);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ]);
            }

            $demoCustomerIds = $customers->pluck('id');
            $hasCancelledDemoOrder = Order::query()
                ->whereIn('customer_id', $demoCustomerIds)
                ->where('status', 'cancelled')
                ->exists();

            if (! $hasCancelledDemoOrder) {
                $customer = $customers->first();
                $product = $products->first();
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'status' => 'cancelled',
                    'total' => $product->price,
                    'ordered_at' => now(),
                ]);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->price,
                ]);
            }
        }
    }
}
