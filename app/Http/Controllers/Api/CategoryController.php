<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->with(['parent', 'children'])
            ->active()
            ->when($request->boolean('featured'), function ($query) {
                $query->featured();
            })
            ->when($request->boolean('root_only'), function ($query) {
                $query->root();
            })
            ->when($request->input('parent_id'), function ($query, $parentId) {
                $query->where('parent_id', $parentId);
            })
            ->ordered()
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        $category->load(['parent', 'children', 'activeProducts' => function ($query) {
            $query->active()->published()->with('variants');
        }]);

        return new CategoryResource($category);
    }

    public function store(Request $request): CategoryResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'integer|min:0',
            'meta' => 'nullable|array',
        ]);

        $category = Category::create($validated);

        return new CategoryResource($category);
    }

    public function update(Request $request, Category $category): CategoryResource
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'integer|min:0',
            'meta' => 'nullable|array',
        ]);

        $category->update($validated);

        return new CategoryResource($category);
    }

    public function destroy(Category $category): Response
    {
        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories',
            ], 422);
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with products',
            ], 422);
        }

        $category->delete();

        return response()->noContent();
    }

    public function tree(Request $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->active()
            ->root()
            ->with(['children' => function ($query) {
                $query->active()->ordered();
            }])
            ->ordered()
            ->get();

        return CategoryResource::collection($categories);
    }
}
