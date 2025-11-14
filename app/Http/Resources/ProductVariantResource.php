<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'product_id' => $this->product_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'compare_price' => $this->compare_price,
            'formatted_compare_price' => $this->formatted_compare_price,
            'discount_percentage' => $this->discount_percentage,
            'quantity' => $this->quantity,
            'is_active' => $this->is_active,
            'is_in_stock' => $this->is_in_stock,
            'attributes' => $this->attributes,
            'attribute_string' => $this->attribute_string,
            'image' => $this->image,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'product' => $this->when($this->relationLoaded('product'), function () {
                return new ProductResource($this->product);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
