<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'subtotal' => $this->subtotal,
            'formatted_subtotal' => number_format($this->subtotal, 2),
            'tax_amount' => $this->tax_amount,
            'formatted_tax_amount' => number_format($this->tax_amount, 2),
            'shipping_amount' => $this->shipping_amount,
            'formatted_shipping_amount' => number_format($this->shipping_amount, 2),
            'discount_amount' => $this->discount_amount,
            'formatted_discount_amount' => number_format($this->discount_amount, 2),
            'total' => $this->total,
            'formatted_total' => number_format($this->total, 2),
            'currency' => $this->currency,
            'coupon_data' => $this->coupon_data,
            'item_count' => $this->getItemCount(),
            'is_empty' => $this->isEmpty(),
            'items' => $this->when($this->relationLoaded('items'), function () {
                return CartItemResource::collection($this->items);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
