<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ValidCoupon implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        private ?float $minimumAmount = null,
        private ?int $userId = null
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $coupon = DB::table('coupons')
            ->where('code', strtoupper($value))
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$coupon) {
            return false;
        }

        // Check minimum amount
        if ($this->minimumAmount && $coupon->minimum_amount > $this->minimumAmount) {
            return false;
        }

        // Check usage limits
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return false;
        }

        // Check user-specific limits
        if ($this->userId && $coupon->usage_limit_per_user) {
            $userUsage = DB::table('orders')
                ->join('carts', 'orders.id', '=', 'carts.id')
                ->where('carts.coupon_data', 'like', '%"code":"' . strtoupper($value) . '"%')
                ->where('orders.user_id', $this->userId)
                ->count();

            if ($userUsage >= $coupon->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('validation.ecommerce.coupon_invalid');
    }
}
