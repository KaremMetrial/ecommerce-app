<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Coupon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateCouponUsage implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        if (!$order->coupon_id) {
            return;
        }

        try {
            $coupon = Coupon::findOrFail($order->coupon_id);

            // Increment used count
            $coupon->increment('used_count');

            // Check if coupon has reached its usage limit
            if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                $coupon->update(['is_active' => false]);
            }

            // Fire coupon used event
            event(new \App\Events\CouponUsed(
                $coupon,
                $order,
                $order->discount_amount
            ));

            Log::info('Coupon usage updated', [
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
                'order_id' => $order->id,
                'used_count' => $coupon->used_count,
                'usage_limit' => $coupon->usage_limit,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update coupon usage', [
                'order_id' => $order->id,
                'coupon_id' => $order->coupon_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderCreated $event, \Throwable $exception): void
    {
        Log::error('Failed to update coupon usage', [
            'order_id' => $event->order->id,
            'coupon_id' => $event->order->coupon_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
