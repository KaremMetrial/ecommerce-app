<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['categories', 'activeVariants'])
            ->active()
            ->published()
            ->when($request->input('category_id'), function ($query, $categoryId) {
                $query->byCategory($categoryId);
            })
            ->when($request->input('featured'), function ($query) {
                $query->featured();
            })
            ->when($request->input('in_stock'), function ($query) {
                $query->inStock();
            })
            ->when($request->input('min_price'), function ($query, $minPrice) {
                $query->where('price', '>=', $minPrice);
            })
            ->when($request->input('max_price'), function ($query, $maxPrice) {
                $query->where('price', '<=', $maxPrice);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->input('sort'), function ($query, $sort) {
                switch ($sort) {
                    case 'price_low':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'price_high':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'name_asc':
                        $query->orderBy('name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('name', 'desc');
                        break;
                    case 'newest':
                        $query->orderBy('created_at', 'desc');
                        break;
                    case 'oldest':
                        $query->orderBy('created_at', 'asc');
                        break;
                    default:
                        $query->orderBy('created_at', 'desc');
                }
            }, function ($query) {
                $query->orderBy('created_at', 'desc');
            })
            ->paginate($request->input('per_page', 12));

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        $product->load(['categories', 'activeVariants']);

        return new ProductResource($product);
    }

    public function store(Request $request): ProductResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'track_quantity' => 'boolean',
            'quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_digital' => 'boolean',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'images' => 'nullable|array',
            'attributes' => 'nullable|array',
            'meta' => 'nullable|array',
            'published_at' => 'nullable|date',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $product = Product::create($validated);

        if (!empty($validated['category_ids'])) {
            $product->categories()->attach($validated['category_ids']);
        }

        $product->load(['categories', 'activeVariants']);

        return new ProductResource($product);
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'price' => 'sometimes|required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'track_quantity' => 'boolean',
            'quantity' => 'sometimes|required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_digital' => 'boolean',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'images' => 'nullable|array',
            'attributes' => 'nullable|array',
            'meta' => 'nullable|array',
            'published_at' => 'nullable|date',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $product->update($validated);

        if (isset($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        $product->load(['categories', 'activeVariants']);

        return new ProductResource($product);
    }

    public function destroy(Product $product): Response
    {
        // Check if product has order items
        if ($product->orderItems()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete product with order history',
            ], 422);
        }

        $product->delete();

        return response()->noContent();
    }

    public function featured(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['categories', 'activeVariants'])
            ->active()
            ->published()
            ->featured()
            ->inStock()
            ->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 8))
            ->get();

        return ProductResource::collection($products);
    }

    public function related(Product $product, Request $request): AnonymousResourceCollection
    {
        $categoryIds = $product->categories()->pluck('categories.id');

        $products = Product::query()
            ->with(['categories', 'activeVariants'])
            ->active()
            ->published()
            ->inStock()
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->inRandomOrder()
            ->limit($request->input('limit', 4))
            ->get();

        return ProductResource::collection($products);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $products = Product::query()
            ->with(['categories', 'activeVariants'])
            ->active()
            ->published()
            ->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->input('q')}%")
                      ->orWhere('description', 'like', "%{$request->input('q')}%")
                      ->orWhere('sku', 'like', "%{$request->input('q')}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 12));

        return ProductResource::collection($products);
    }
}
