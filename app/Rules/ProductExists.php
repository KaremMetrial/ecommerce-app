<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ProductExists implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        private ?int $excludeId = null,
        private ?bool $activeOnly = true,
        private ?bool $inStockOnly = false
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $query = DB::table('products')
            ->where('id', $value);

        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($this->activeOnly) {
            $query->where('is_active', true);
        }

        if ($this->inStockOnly) {
            $query->where(function ($q) {
                $q->where('track_quantity', false)
                  ->orWhere('quantity', '>', 0);
            });
        }

        return $query->exists();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        $message = __('validation.ecommerce.product_not_available');

        if ($this->activeOnly && $this->inStockOnly) {
            return __('validation.ecommerce.product_active_and_in_stock');
        } elseif ($this->activeOnly) {
            return __('validation.ecommerce.product_active');
        } elseif ($this->inStockOnly) {
            return __('validation.ecommerce.product_in_stock');
        }

        return $message;
    }
}
