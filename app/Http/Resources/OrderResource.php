<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'subtotal' => $this->subtotal,
            'formatted_subtotal' => $this->formatted_subtotal,
            'tax_amount' => $this->tax_amount,
            'formatted_tax_amount' => $this->formatted_tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'formatted_shipping_amount' => $this->formatted_shipping_amount,
            'discount_amount' => $this->discount_amount,
            'formatted_discount_amount' => $this->formatted_discount_amount,
            'total' => $this->total,
            'formatted_total' => $this->formatted_total,
            'currency' => $this->currency,
            'shipping_address' => $this->shipping_address,
            'shipping_address_string' => $this->shipping_address_string,
            'billing_address' => $this->billing_address,
            'billing_address_string' => $this->billing_address_string,
            'notes' => $this->notes,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'item_count' => $this->item_count,
            'total_quantity' => $this->total_quantity,
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_confirmed' => $this->canBeConfirmed(),
            'can_be_processed' => $this->canBeProcessed(),
            'can_be_shipped' => $this->canBeShipped(),
            'can_be_delivered' => $this->canBeDelivered(),
            'can_be_refunded' => $this->canBeRefunded(),
            'items' => $this->when($this->relationLoaded('items'), function () {
                return OrderItemResource::collection($this->items);
            }),
            'payment' => $this->when($this->relationLoaded('payment'), function () {
                return new PaymentResource($this->payment);
            }),
            'payments' => $this->when($this->relationLoaded('payments'), function () {
                return PaymentResource::collection($this->payments);
            }),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
