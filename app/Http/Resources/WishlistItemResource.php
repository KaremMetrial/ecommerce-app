<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
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
            'wishlist_id' => $this->wishlist_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'notes' => $this->notes,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'product_slug' => $this->product_slug,
            'product_image' => $this->product_image,
            'variant_name' => $this->variant_name,
            'variant_sku' => $this->variant_sku,
            'variant_attributes' => $this->variant_attributes,
            'display_name' => $this->display_name,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'compare_price' => $this->compare_price,
            'formatted_compare_price' => $this->formatted_compare_price,
            'discount_percentage' => $this->discount_percentage,
            'is_available' => $this->is_available,
            'stock_level' => $this->stock_level,
            'can_be_added_to_cart' => $this->canBeAddedToCart(),
            'product' => $this->when($this->relationLoaded('product'), function () {
                return new ProductResource($this->product);
            }),
            'variant' => $this->when($this->relationLoaded('variant'), function () {
                return new ProductVariantResource($this->variant);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
