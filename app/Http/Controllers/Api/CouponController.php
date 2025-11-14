<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $coupons = Coupon::query()
            ->when($request->boolean('active'), function ($query) {
                $query->active();
            })
            ->when($request->boolean('valid'), function ($query) {
                $query->valid();
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return CouponResource::collection($coupons);
    }

    public function show(Coupon $coupon): CouponResource
    {
        return new CouponResource($coupon);
    }

    public function store(Request $request): CouponResource
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'required|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
        ]);

        $coupon = Coupon::create($validated);

        return new CouponResource($coupon);
    }

    public function update(Request $request, Coupon $coupon): CouponResource
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:coupons,code,' . $coupon->id,
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'sometimes|required|in:fixed,percentage',
            'value' => 'sometimes|required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'sometimes|required|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
        ]);

        $coupon->update($validated);

        return new CouponResource($coupon);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully',
        ]);
    }

    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|exists:coupons,code',
            'amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $validated['code'])->first();

        if (!$coupon->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon is not valid',
                'reason' => $this->getInvalidReason($coupon),
            ]);
        }

        if (!$coupon->isValidForAmount($validated['amount'])) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon is not valid for this order amount',
                'reason' => 'Minimum order amount not met',
            ]);
        }

        $user = $request->user();
        if ($user && !$coupon->isValidForUser($user)) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon is not valid for this user',
                'reason' => 'Usage limit per user exceeded',
            ]);
        }

        $discount = $coupon->calculateDiscount($validated['amount']);

        return response()->json([
            'valid' => true,
            'message' => 'Coupon is valid',
            'coupon' => new CouponResource($coupon),
            'discount_amount' => $discount,
            'formatted_discount' => number_format($discount, 2),
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|exists:coupons,code',
        ]);

        $user = $request->user();
        $cart = $user->getCart();

        $coupon = Coupon::where('code', $validated['code'])->first();

        if (!$coupon->isValidForUser($user)) {
            return response()->json([
                'message' => 'Coupon is not valid for this user',
            ], 422);
        }

        if (!$coupon->isValidForAmount($cart->subtotal)) {
            return response()->json([
                'message' => 'Coupon is not valid for this order amount',
            ], 422);
        }

        if ($cart->applyCoupon($coupon)) {
            $coupon->incrementUsage();

            return response()->json([
                'message' => 'Coupon applied successfully',
                'cart' => $cart->load(['items.product', 'items.variant']),
            ]);
        }

        return response()->json([
            'message' => 'Failed to apply coupon',
        ], 422);
    }

    private function getInvalidReason(Coupon $coupon): string
    {
        if (!$coupon->is_active) {
            return 'Coupon is inactive';
        }

        if ($coupon->is_upcoming) {
            return 'Coupon is not yet active';
        }

        if ($coupon->is_expired) {
            return 'Coupon has expired';
        }

        if ($coupon->is_used_up) {
            return 'Coupon usage limit reached';
        }

        return 'Coupon is not valid';
    }
}
