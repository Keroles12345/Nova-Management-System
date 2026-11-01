<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(['pending', 'processing', 'completed', 'cancelled'])],
        ]);

        $ordersQuery = Order::query()
            ->with(['customer:id,name,email', 'items.product:id,name,sku'])
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('ordered_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->where('ordered_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status));

        $search = trim($filters['q'] ?? '');

        if ($search !== '') {
            $ordersQuery->where(function (Builder $query) use ($search): void {
                $query->whereHas('customer', function (Builder $customerQuery) use ($search): void {
                    $customerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('items.product', function (Builder $productQuery) use ($search): void {
                    $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        return view('orders.index', [
            'filters' => [
                'q' => $search,
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'orders' => $ordersQuery->latest('ordered_at')->paginate(15)->withQueryString(),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'email']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'price']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_mode' => ['required', Rule::in(['existing', 'new'])],
            'customer_id' => ['nullable', 'required_if:customer_mode,existing', 'integer', 'exists:customers,id'],
            'new_customer_name' => ['nullable', 'required_if:customer_mode,new', 'string', 'max:255'],
            'new_customer_email' => ['nullable', 'required_if:customer_mode,new', 'email', 'max:255', 'unique:customers,email'],
            'status' => ['required', Rule::in(['pending', 'processing', 'completed', 'cancelled'])],
            'ordered_at' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data): void {
            $customerId = $data['customer_mode'] === 'new'
                ? Customer::create([
                    'name' => $data['new_customer_name'],
                    'email' => $data['new_customer_email'],
                ])->id
                : $data['customer_id'];

            $order = Order::create([
                'customer_id' => $customerId,
                'status' => $data['status'],
                'ordered_at' => $data['ordered_at'],
                'total' => 0,
            ]);

            $this->syncOrderItems($order, $data['items']);
        });

        return back()->with('success', 'Order created successfully.');
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $this->validatedOrderData($request);

        DB::transaction(function () use ($data, $order): void {
            $order->update([
                'customer_id' => $data['customer_id'],
                'status' => $data['status'],
                'ordered_at' => $data['ordered_at'],
            ]);

            $this->syncOrderItems($order, $data['items']);
        });

        return back()->with('success', 'Order updated successfully.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'completed', 'cancelled'])],
        ]);

        $order->update($data);

        return back()->with('success', 'Order status updated.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Order cancelled. The order record was kept.');
    }

    /**
     * @return array{
     *     customer_id: int,
     *     status: string,
     *     ordered_at: string|null,
     *     items: array<int, array{product_id: int, quantity: int}>
     * }
     */
    private function validatedOrderData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'status' => ['required', Rule::in(['pending', 'processing', 'completed', 'cancelled'])],
            'ordered_at' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);
    }

    /**
     * @param array<int, array{product_id: int, quantity: int}> $items
     */
    private function syncOrderItems(Order $order, array $items): void
    {
        $products = Product::query()
            ->whereKey(collect($items)->pluck('product_id'))
            ->get()
            ->keyBy('id');
        $total = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $unitPrice = (float) $product->price;
            $total += $unitPrice * $item['quantity'];
            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
            ];
        }

        $order->items()->delete();
        $order->items()->createMany($orderItems);
        $order->update(['total' => $total]);
    }
}
