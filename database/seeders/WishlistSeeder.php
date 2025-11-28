<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Seeder;

class WishlistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Create 1-2 wishlists per user
            $wishlistCount = rand(1, 2);

            for ($i = 0; $i < $wishlistCount; $i++) {
                $isDefault = $i === 0; // First wishlist is default
                $isPublic = rand(0, 1) === 1; // 50% chance of being public

                $wishlist = Wishlist::factory()->create([
                    'user_id' => $user->id,
                    'is_public' => $isPublic,
                ]);

                // Add 1-5 items to each wishlist
                $itemCount = rand(1, 5);
                $wishlist->items()->createMany(
                    \App\Models\WishlistItem::factory()->count($itemCount)->make()->toArray()
                );
            }
        }

        // Create some special occasion wishlists
        Wishlist::factory()->count(5)->forBirthday()->withItems(rand(3, 8))->create();
        Wishlist::factory()->count(3)->forWedding()->withItems(rand(5, 15))->create();
        Wishlist::factory()->count(4)->forHolidays()->withItems(rand(2, 10))->create();

        // Create some shared wishlists
        Wishlist::factory()->count(8)->shared()->withItems(rand(2, 6))->create();

        // Create some wishlists with high priority items
        Wishlist::factory()->count(6)->withHighPriorityItems()->create();

        // Create some wishlists with notifications enabled
        Wishlist::factory()->count(10)->withPriceNotifications()->create();
        Wishlist::factory()->count(8)->withStockNotifications()->create();
    }
}
