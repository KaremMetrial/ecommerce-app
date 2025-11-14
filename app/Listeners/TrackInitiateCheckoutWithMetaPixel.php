<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\MetaPixelService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TrackInitiateCheckoutWithMetaPixel implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private MetaPixelService $pixel)
    {
    }

    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order->loadMissing(['items']);

            // Build cart-like payload from order
            $items = [];
            foreach ($order->items as $item) {
                $items[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            }

            $cart = [
                'items' => $items,
                'total' => (float) $order->total,
                'currency' => $order->currency ?? 'USD',
            ];

            $userId = (string) $order->user_id;
            $this->pixel->trackInitiateCheckout($cart, $userId);
        } catch (\Throwable $e) {
            // Swallow exceptions to not affect business flow
        }
    }
}
