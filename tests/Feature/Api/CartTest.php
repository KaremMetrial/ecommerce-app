<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\Response;

class CartTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test getting cart
     */
    public function test_can_get_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cart');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'user_id',
                    'subtotal',
                    'formatted_subtotal',
                    'tax_amount',
                    'formatted_tax_amount',
                    'shipping_amount',
                    'formatted_shipping_amount',
                    'discount_amount',
                    'formatted_discount_amount',
                    'total',
                    'formatted_total',
                    'currency',
                    'coupon_data',
                    'item_count',
                    'is_empty',
                    'items' => [
                        '*' => [
                            'id',
                            'product_id',
                            'product_variant_id',
                            'quantity',
                            'unit_price',
                            'formatted_unit_price',
                            'total_price',
                            'formatted_total_price',
                            'product_name',
                            'product_sku',
                            'product_slug',
                            'product_image',
                            'variant_name',
                            'variant_sku',
                            'variant_attributes',
                            'is_available',
                            'stock_level',
                            'can_increase_quantity',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test adding item to cart
     */
    public function test_can_add_item_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'quantity' => 50,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'subtotal',
                    'total',
                    'item_count',
                    'items' => [
                        '*' => [
                            'product_id',
                            'quantity',
                            'unit_price',
                            'total_price',
                            'product_name',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->price,
            'total_price' => $product->price * 2,
        ]);
    }

    /**
     * Test adding item with insufficient stock
     */
    public function test_cannot_add_item_with_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'quantity' => 1,
            'track_quantity' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 5,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test updating cart item quantity
     */
    public function test_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $cartItem = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total_price' => $product->price,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/cart/items/{$cartItem->id}", [
                'quantity' => 3,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'quantity',
                            'total_price',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 3,
            'total_price' => $product->price * 3,
        ]);
    }

    /**
     * Test removing cart item
     */
    public function test_can_remove_cart_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $cartItem = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total_price' => $product->price,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/cart/items/{$cartItem->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'quantity',
                            'total_price',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    /**
     * Test clearing cart
     */
    public function test_can_clear_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => 1, 'quantity' => 1]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/cart');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'item_count' => 0,
                    'is_empty' => true,
                    'items' => [],
                ],
            ]);

        $this->assertDatabaseMissing('cart_items');
    }

    /**
     * Test unauthorized cart access
     */
    public function test_unauthorized_user_cannot_access_cart(): void
    {
        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
                'code' => 401,
            ]);
    }

    /**
     * Test applying coupon to cart
     */
    public function test_can_apply_coupon_to_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => 1, 'quantity' => 1, 'unit_price' => 100, 'total_price' => 100]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cart/coupon', [
                'code' => 'TEST10',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid coupon code.',
            ]);
    }
}
