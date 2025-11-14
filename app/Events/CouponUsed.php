<?php

namespace App\Events;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CouponUsed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Coupon $coupon,
        public Order $order,
        public float $discountAmount
    ) {}

    /**
     * Get channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->order->user_id),
            new PrivateChannel('admin.coupons'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'coupon.used';
    }

    /**
     * Get data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'coupon_id' => $this->coupon->id,
            'coupon_code' => $this->coupon->code,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'user_id' => $this->order->user_id,
            'discount_amount' => $this->discountAmount,
            'currency' => $this->order->currency,
            'used_count' => $this->coupon->used_count,
            'usage_limit' => $this->coupon->usage_limit,
        ];
    }
}
