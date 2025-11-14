<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Http\Resources\WishlistItemResource;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WishlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $wishlists = $request->user()
            ->wishlists()
            ->with(['items.product', 'items.variant'])
            ->get();

        return WishlistResource::collection($wishlists);
    }

    public function show(Request $request, Wishlist $wishlist): WishlistResource
    {
        // Ensure user can only view their own wishlists
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $wishlist->load(['items.product', 'items.variant']);

        return new WishlistResource($wishlist);
    }

    public function store(Request $request): WishlistResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $wishlist = $request->user()->wishlists()->create($validated);

        return new WishlistResource($wishlist);
    }

    public function update(Request $request, Wishlist $wishlist): WishlistResource
    {
        // Ensure user can only update their own wishlists
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $wishlist->update($validated);

        return new WishlistResource($wishlist);
    }

    public function destroy(Request $request, Wishlist $wishlist): Response
    {
        // Ensure user can only delete their own wishlists
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $wishlist->delete();

        return response()->noContent();
    }

    public function addItem(Request $request, Wishlist $wishlist): JsonResponse
    {
        // Ensure user can only add to their own wishlists
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $variant = null;

        if (!empty($validated['variant_id'])) {
            $variant = ProductVariant::findOrFail($validated['variant_id']);
        }

        // Check if item already exists in wishlist
        if ($wishlist->hasProduct($product, $variant)) {
            return response()->json([
                'message' => 'Item already exists in wishlist',
            ], 422);
        }

        $wishlistItem = $wishlist->addItem($product, $variant, $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Item added to wishlist successfully',
            'item' => new WishlistItemResource($wishlistItem->load(['product', 'variant'])),
        ]);
    }

    public function removeItem(Request $request, Wishlist $wishlist, $itemId): JsonResponse
    {
        // Ensure user can only remove from their own wishlists
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $wishlistItem = $wishlist->items()->findOrFail($itemId);
        $wishlist->removeItem($wishlistItem);

        return response()->json([
            'message' => 'Item removed from wishlist successfully',
        ]);
    }

    public function moveToCart(Request $request, Wishlist $wishlist, $itemId): JsonResponse
    {
        // Ensure user can only move from their own wishlists
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $wishlistItem = $wishlist->items()->findOrFail($itemId);

        if (!$wishlistItem->canBeAddedToCart()) {
            return response()->json([
                'message' => 'Item cannot be added to cart',
            ], 422);
        }

        $cart = $request->user()->getCart();
        $cartItem = $wishlistItem->addToCart($cart, $validated['quantity']);

        if ($cartItem) {
            $cart->calculateTotals();

            return response()->json([
                'message' => 'Item moved to cart successfully',
                'cart_item' => $cartItem,
            ]);
        }

        return response()->json([
            'message' => 'Failed to move item to cart',
        ], 422);
    }

    public function clear(Request $request, Wishlist $wishlist): JsonResponse
    {
        // Ensure user can only clear their own wishlists
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $wishlist->clear();

        return response()->json([
            'message' => 'Wishlist cleared successfully',
        ]);
    }

    public function getDefault(Request $request): WishlistResource
    {
        $wishlist = $request->user()->getDefaultWishlist();
        $wishlist->load(['items.product', 'items.variant']);

        return new WishlistResource($wishlist);
    }

    public function checkProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $variant = null;

        if (!empty($validated['variant_id'])) {
            $variant = ProductVariant::findOrFail($validated['variant_id']);
        }

        $wishlist = $request->user()->getDefaultWishlist();
        $exists = $wishlist->hasProduct($product, $variant);

        return response()->json([
            'exists' => $exists,
            'wishlist_item' => $exists ? $wishlist->getItemForProduct($product, $variant) : null,
        ]);
    }
}
