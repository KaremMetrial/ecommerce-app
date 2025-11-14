<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // 70% chance of having an active cart
            if (rand(1, 100) <= 70) {
                $cart = Cart::factory()->create([
                    'user_id' => $user->id,
                ]);

                // Add 1-5 items to each cart
                $itemCount = rand(1, 5);
                $products = Product::inRandomOrder()->take($itemCount)->get();

                foreach ($products as $product) {
                    // Check if product has variants
                    if ($product->variants()->exists()) {
                        $variant = $product->variants()->inRandomOrder()->first();
                        CartItem::factory()->create([
                            'cart_id' => $cart->id,
                            'product_id' => $product->id,
                            'product_variant_id' => $variant->id,
                            'quantity' => rand(1, 3),
                        ]);
                    } else {
                        CartItem::factory()->create([
                            'cart_id' => $cart->id,
                            'product_id' => $product->id,
                            'quantity' => rand(1, 3),
                        ]);
                    }
                }
            }
        }

        // Create some abandoned carts (older carts)
        Cart::factory()->count(15)->create([
            'created_at' => now()->subDays(rand(7, 90)),
            'updated_at' => now()->subDays(rand(1, 6)),
        ])->each(function ($cart) {
            // Add items to abandoned carts
            $itemCount = rand(1, 4);
            CartItem::factory()->count($itemCount)->create([
                'cart_id' => $cart->id,
            ]);
        });

        // Create some guest carts
        Cart::factory()->count(20)->guest()->create()->each(function ($cart) {
            // Add items to guest carts
            $itemCount = rand(1, 3);
            CartItem::factory()->count($itemCount)->create([
                'cart_id' => $cart->id,
            ]);
        });

        // Create some carts with high-value items
        Cart::factory()->count(5)->create()->each(function ($cart) {
            // Add expensive items
            $itemCount = rand(1, 2);
            CartItem::factory()->count($itemCount)->create([
                'cart_id' => $cart->id,
                'quantity' => 1,
            ]);
        });
    }
}
