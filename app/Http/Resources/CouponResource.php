<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'value' => $this->value,
            'formatted_value' => $this->formatted_value,
            'minimum_amount' => $this->minimum_amount,
            'formatted_minimum_amount' => $this->formatted_minimum_amount,
            'usage_limit' => $this->usage_limit,
            'usage_limit_per_user' => $this->usage_limit_per_user,
            'used_count' => $this->used_count,
            'usage_remaining' => $this->usage_remaining,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'is_active' => $this->is_active,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->is_expired,
            'is_upcoming' => $this->is_upcoming,
            'is_used_up' => $this->is_used_up,
            'applicable_products' => $this->applicable_products,
            'applicable_categories' => $this->applicable_categories,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
