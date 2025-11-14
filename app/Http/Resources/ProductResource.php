<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'sku' => $this->sku,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'compare_price' => $this->compare_price,
            'formatted_compare_price' => $this->formatted_compare_price,
            'discount_percentage' => $this->discount_percentage,
            'cost_price' => $this->cost_price,
            'track_quantity' => $this->track_quantity,
            'quantity' => $this->quantity,
            'min_stock_level' => $this->min_stock_level,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_digital' => $this->is_digital,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'images' => $this->images,
            'first_image' => $this->first_image,
            'attributes' => $this->attributes,
            'meta' => $this->meta,
            'published_at' => $this->published_at,
            'is_in_stock' => $this->is_in_stock,
            'is_low_stock' => $this->is_low_stock,
            'has_variants' => $this->has_variants,
            'total_stock' => $this->total_stock,
            'categories' => $this->when($this->relationLoaded('categories'), function () {
                return CategoryResource::collection($this->categories);
            }),
            'variants' => $this->when($this->relationLoaded('activeVariants'), function () {
                return ProductVariantResource::collection($this->activeVariants);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
