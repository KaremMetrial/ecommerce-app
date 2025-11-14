<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'product_slug' => $this->product_slug,
            'product_image' => $this->product_image,
            'variant_name' => $this->variant_name,
            'variant_sku' => $this->variant_sku,
            'variant_attributes' => $this->variant_attributes,
            'display_name' => $this->display_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'formatted_unit_price' => $this->formatted_unit_price,
            'total_price' => $this->total_price,
            'formatted_total_price' => $this->formatted_total_price,
            'product_data' => $this->product_data,
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
