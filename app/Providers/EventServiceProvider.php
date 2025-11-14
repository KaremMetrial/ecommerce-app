<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Order Events
        \App\Events\OrderCreated::class => [
            \App\Listeners\SendOrderConfirmationEmail::class,
            \App\Listeners\UpdateCouponUsage::class,
            \App\Listeners\UpdateProductStock::class,
            \App\Listeners\TrackInitiateCheckoutWithMetaPixel::class,
        ],

        \App\Events\OrderStatusChanged::class => [
            \App\Listeners\UpdateProductStock::class,
        ],

        // Payment Events
        \App\Events\PaymentCompleted::class => [
            \App\Listeners\SendPaymentConfirmationEmail::class,
            \App\Listeners\TrackPurchaseWithMetaPixel::class,
        ],

        \App\Events\PaymentFailed::class => [
            // Add listeners for payment failures
        ],

        \App\Events\RefundCompleted::class => [
            // Add listeners for refund completions
        ],

        // Product Events
        \App\Events\ProductOutOfStock::class => [
            \App\Listeners\NotifyAdminOfLowStock::class,
        ],

        // Coupon Events
        \App\Events\CouponUsed::class => [
            // Add listeners for coupon usage tracking
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
