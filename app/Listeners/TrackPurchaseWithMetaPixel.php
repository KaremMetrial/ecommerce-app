<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Services\MetaPixelService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TrackPurchaseWithMetaPixel implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private MetaPixelService $pixel)
    {
    }

    public function handle(PaymentCompleted $event): void
    {
        try {
            $payment = $event->payment->loadMissing(['order.items']);
            $order = $payment->order;

            $items = [];
            foreach ($order->items as $item) {
                $items[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            }

            $payload = [
                'order_number' => $order->order_number,
                'total' => (float) $order->total,
                'currency' => $order->currency ?? 'USD',
                'items' => $items,
            ];

            $this->pixel->trackPurchase($payload, (string) $order->user_id);
        } catch (\Throwable $e) {
            // Do not interrupt main flow
        }
    }
}
