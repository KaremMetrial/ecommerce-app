<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome Discount - 10% Off',
                'description' => 'Get 10% off your first order as a welcome gift!',
                'type' => 'percentage',
                'value' => 10.00,
                'minimum_amount' => 50.00,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 1,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'SUMMER25',
                'name' => 'Summer Sale - 25% Off',
                'description' => 'Summer special! Get 25% off on all summer items.',
                'type' => 'percentage',
                'value' => 25.00,
                'minimum_amount' => 100.00,
                'usage_limit' => 500,
                'usage_limit_per_user' => 3,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'FLAT20',
                'name' => 'Flat $20 Off',
                'description' => 'Get $20 off on orders over $100',
                'type' => 'fixed',
                'value' => 20.00,
                'minimum_amount' => 100.00,
                'usage_limit' => 200,
                'usage_limit_per_user' => 2,
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
                'is_active' => true,
            ],
            [
                'code' => 'NEWUSER15',
                'name' => 'New User Special - 15% Off',
                'description' => 'Special discount for new users only!',
                'type' => 'percentage',
                'value' => 15.00,
                'minimum_amount' => 25.00,
                'usage_limit' => null, // Unlimited usage
                'usage_limit_per_user' => 1,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Free Shipping',
                'description' => 'Get free shipping on your order (equivalent to $10 off)',
                'type' => 'fixed',
                'value' => 10.00,
                'minimum_amount' => 75.00,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 5,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::create($couponData);
        }
    }
}
