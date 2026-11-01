<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
        ]);
        $search = trim($filters['q'] ?? '');

        $productsQuery = Product::query()
            ->withCount('orderItems')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['min_price'] ?? null, fn (Builder $query, string $price) => $query->where('price', '>=', $price))
            ->when($filters['max_price'] ?? null, fn (Builder $query, string $price) => $query->where('price', '<=', $price));

        return view('products.index', [
            'filters' => [
                'q' => $search,
                'category' => $filters['category'] ?? '',
                'min_price' => $filters['min_price'] ?? '',
                'max_price' => $filters['max_price'] ?? '',
            ],
            'categories' => Product::query()->distinct()->orderBy('category')->pluck('category'),
            'products' => $productsQuery->orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validatedProductData($request));

        return back()->with('success', 'Product created successfully.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validatedProductData($request, $product));

        return back()->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return back()->withErrors([
                'product' => 'This product is used in order history and cannot be deleted.',
            ]);
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    /**
     * @return array{name: string, sku: string, category: string, price: string}
     */
    private function validatedProductData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product)],
            'category' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);
    }
}
