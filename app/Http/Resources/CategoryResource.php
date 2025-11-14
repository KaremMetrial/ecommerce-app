<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'image' => $this->image,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'meta' => $this->meta,
            'parent_id' => $this->parent_id,
            'parent' => $this->when($this->relationLoaded('parent'), function () {
                return new CategoryResource($this->parent);
            }),
            'children' => $this->when($this->relationLoaded('children'), function () {
                return CategoryResource::collection($this->children);
            }),
            'products_count' => $this->when($this->relationLoaded('activeProducts'), function () {
                return $this->activeProducts->count();
            }),
            'products' => $this->when($this->relationLoaded('activeProducts'), function () {
                return ProductResource::collection($this->activeProducts);
            }),
            'full_path' => $this->full_path,
            'full_slug' => $this->full_slug,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
