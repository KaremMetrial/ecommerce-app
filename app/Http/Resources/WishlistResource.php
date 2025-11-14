<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
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
            'name' => $this->name,
            'is_public' => $this->is_public,
            'notes' => $this->notes,
            'item_count' => $this->getItemCount(),
            'total_value' => $this->total_value,
            'formatted_total_value' => $this->formatted_total_value,
            'items' => $this->when($this->relationLoaded('items'), function () {
                return WishlistItemResource::collection($this->items);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
