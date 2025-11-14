<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function index(Request $request): CartResource
    {
        $cart = $this->getUserCart($request);

        $cart->load(['items.product', 'items.variant']);

        return new CartResource($cart);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $variant = null;

        if (!empty($validated['variant_id'])) {
            $variant = ProductVariant::findOrFail($validated['variant_id']);
        }

        // Check if product/variant can be purchased
        if ($variant) {
            if (!$variant->canBePurchased($validated['quantity'])) {
                return response()->json([
                    'message' => 'Product variant is not available in the requested quantity',
                ], 422);
            }
        } else {
            if (!$product->canBePurchased($validated['quantity'])) {
                return response()->json([
                    'message' => 'Product is not available in the requested quantity',
                ], 422);
            }
        }

        $cart = $this->getUserCart($request);
        $cartItem = $cart->addItem($product, $validated['quantity'], $variant);
        $cart->calculateTotals();

        $cart->load(['items.product', 'items.variant']);

        return response()->json([
            'message' => 'Item added to cart successfully',
            'cart' => new CartResource($cart),
        ]);
    }

    public function updateItem(Request $request, Cart $cart, $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = $cart->items()->findOrFail($itemId);

        // Check if product/variant can be purchased with new quantity
        if ($cartItem->variant) {
            if (!$cartItem->variant->canBePurchased($validated['quantity'])) {
                return response()->json([
                    'message' => 'Product variant is not available in the requested quantity',
                ], 422);
            }
        } else {
            if (!$cartItem->product->canBePurchased($validated['quantity'])) {
                return response()->json([
                    'message' => 'Product is not available in the requested quantity',
                ], 422);
            }
        }

        $cart->updateItemQuantity($cartItem, $validated['quantity']);

        $cart->load(['items.product', 'items.variant']);

        return response()->json([
            'message' => 'Cart item updated successfully',
            'cart' => new CartResource($cart),
        ]);
    }

    public function removeItem(Request $request, Cart $cart, $itemId): JsonResponse
    {
        $cartItem = $cart->items()->findOrFail($itemId);
        $cart->removeItem($cartItem);

        $cart->load(['items.product', 'items.variant']);

        return response()->json([
            'message' => 'Item removed from cart successfully',
            'cart' => new CartResource($cart),
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getUserCart($request);
        $cart->clear();

        return response()->json([
            'message' => 'Cart cleared successfully',
            'cart' => new CartResource($cart),
        ]);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|exists:coupons,code',
        ]);

        $cart = $this->getUserCart($request);
        $coupon = \App\Models\Coupon::where('code', $validated['code'])->first();

        if (!$coupon->isValidForAmount($cart->subtotal)) {
            return response()->json([
                'message' => 'Coupon is not valid for this order amount',
            ], 422);
        }

        if ($cart->applyCoupon($coupon)) {
            $cart->load(['items.product', 'items.variant']);

            return response()->json([
                'message' => 'Coupon applied successfully',
                'cart' => new CartResource($cart),
            ]);
        }

        return response()->json([
            'message' => 'Failed to apply coupon',
        ], 422);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->getUserCart($request);
        $cart->removeCoupon();

        $cart->load(['items.product', 'items.variant']);

        return response()->json([
            'message' => 'Coupon removed successfully',
            'cart' => new CartResource($cart),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $cart = $this->getUserCart($request);
        $cart->load(['items.product', 'items.variant']);

        return response()->json([
            'item_count' => $cart->getItemCount(),
            'subtotal' => $cart->subtotal,
            'tax_amount' => $cart->tax_amount,
            'shipping_amount' => $cart->shipping_amount,
            'discount_amount' => $cart->discount_amount,
            'total' => $cart->total,
            'coupon' => $cart->coupon_data,
        ]);
    }

    private function getUserCart(Request $request): Cart
    {
        $user = $request->user();

        if ($user) {
            return Cart::getForUser($user);
        }

        // For guest users, use session ID
        $sessionId = $request->session()->getId();
        return Cart::getForSession($sessionId);
    }
}
