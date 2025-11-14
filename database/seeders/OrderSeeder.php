<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Create 1-5 orders per user
            $orderCount = rand(1, 5);

            for ($i = 0; $i < $orderCount; $i++) {
                $orderStatus = $this->getRandomOrderStatus();

                $order = Order::factory()->{$orderStatus}()->create([
                    'user_id' => $user->id,
                ]);

                // Add 1-5 items to each order
                $itemCount = rand(1, 5);
                $products = Product::inRandomOrder()->take($itemCount)->get();

                foreach ($products as $product) {
                    // Check if product has variants
                    if ($product->variants()->exists()) {
                        $variant = $product->variants()->inRandomOrder()->first();
                        OrderItem::factory()->create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'product_variant_id' => $variant->id,
                            'quantity' => rand(1, 3),
                            'unit_price' => $variant->sale_price ?? $variant->price,
                        ]);
                    } else {
                        OrderItem::factory()->create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => rand(1, 3),
                            'unit_price' => $product->sale_price ?? $product->price,
                        ]);
                    }
                }

                // Create payment for the order
                $paymentStatus = $this->getPaymentStatusForOrder($orderStatus);
                Payment::factory()->{$paymentStatus}()->create([
                    'order_id' => $order->id,
                    'amount' => $order->total,
                ]);
            }
        }

        // Create some pending orders
        Order::factory()->count(10)->pending()->withItems(rand(1, 3))->create()->each(function ($order) {
            Payment::factory()->pending()->create([
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
        });

        // Create some processing orders
        Order::factory()->count(15)->processing()->withItems(rand(2, 4))->create()->each(function ($order) {
            Payment::factory()->completed()->create([
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
        });

        // Create some shipped orders
        Order::factory()->count(20)->shipped()->withItems(rand(1, 5))->create()->each(function ($order) {
            Payment::factory()->completed()->create([
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
        });

        // Create some delivered orders
        Order::factory()->count(25)->delivered()->withItems(rand(1, 4))->create()->each(function ($order) {
            Payment::factory()->completed()->create([
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
        });

        // Create some cancelled orders
        Order::factory()->count(8)->cancelled()->withItems(rand(1, 3))->create()->each(function ($order) {
            Payment::factory()->failed()->create([
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
        });

        // Create some refunded orders
        Order::factory()->count(5)->refunded()->withItems(rand(1, 2))->create()->each(function ($order) {
            Payment::factory()->refunded()->create([
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
        });

        // Create some high-value orders
        Order::factory()->count(5)->delivered()->create([
            'total' => rand(1000, 5000),
        ])->each(function ($order) {
            Payment::factory()->completed()->create([
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
            OrderItem::factory()->count(rand(2, 4))->create([
                'order_id' => $order->id,
                'unit_price' => rand(200, 1000),
            ]);
        });
    }

    /**
     * Get a random order status with realistic distribution.
     */
    private function getRandomOrderStatus(): string
    {
        $statuses = [
            'pending' => 15,
            'confirmed' => 10,
            'processing' => 20,
            'shipped' => 25,
            'delivered' => 25,
            'cancelled' => 3,
            'refunded' => 2,
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $status;
            }
        }

        return 'delivered';
    }

    /**
     * Get appropriate payment status for order status.
     */
    private function getPaymentStatusForOrder(string $orderStatus): string
    {
        return match ($orderStatus) {
            'pending' => 'pending',
            'confirmed' => 'completed',
            'processing' => 'completed',
            'shipped' => 'completed',
            'delivered' => 'completed',
            'cancelled' => 'failed',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }
}
