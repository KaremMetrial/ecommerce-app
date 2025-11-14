<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Create 1-3 addresses per user
            $addressCount = rand(1, 3);

            for ($i = 0; $i < $addressCount; $i++) {
                $isDefault = $i === 0; // First address is default

                Address::factory()->create([
                    'user_id' => $user->id,
                    'is_default' => $isDefault,
                    'type' => $i === 0 ? 'both' : (rand(0, 1) ? 'shipping' : 'billing'),
                ]);
            }
        }

        // Create some additional addresses for testing
        Address::factory()->count(20)->create();

        // Create some business addresses
        Address::factory()->count(10)->business()->create();

        // Create some verified addresses
        Address::factory()->count(15)->verified()->create();

        // Create some international addresses
        Address::factory()->count(5)->canada()->create();
        Address::factory()->count(5)->uk()->create();
    }
}
