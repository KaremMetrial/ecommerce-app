<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\Response;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test getting user orders
     */
    public function test_can_get_user_orders(): void
    {
        $user = User::factory()->create();
        Order::factory(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/orders');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'user_id',
                        'status',
                        'status_label',
                        'payment_status',
                        'payment_status_label',
                        'subtotal',
                        'formatted_subtotal',
                        'total',
                        'formatted_total',
                        'created_at',
                        'items' => [
                            '*' => [
                                'id',
                                'product_id',
                                'product_name',
                                'quantity',
                                'unit_price',
                                'formatted_unit_price',
                                'total_price',
                                'formatted_total_price',
                            ],
                        ],
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test creating an order
     */
    public function test_can_create_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'quantity' => 10,
            'is_active' => true,
        ]);

        $orderData = [
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
                'country' => 'US',
                'phone' => '555-1234',
                'email' => 'john@example.com',
            ],
            'payment_method' => 'stripe',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', $orderData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'order_number',
                    'user_id',
                    'status',
                    'total',
                    'formatted_total',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total' => 100,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total_price' => $product->price,
        ]);
    }

    /**
     * Test order not found
     */
    public function test_order_not_found_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/orders/999');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found.',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized order access
     */
    public function test_unauthorized_user_cannot_access_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id + 1]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden',
                'code' => 403,
            ]);
    }

    /**
     * Test order cancellation
     */
    public function test_can_cancel_pending_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'status_label',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test order tracking
     */
    public function test_can_track_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/orders/{$order->id}/track");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_number',
                    'status',
                    'status_label',
                    'payment_status',
                    'payment_status_label',
                    'created_at',
                    'shipped_at',
                    'tracking_info' => [
                        'status',
                        'description',
                        'estimated_delivery',
                    ],
                ],
            ]);
    }

    /**
     * Test order validation
     */
    public function test_order_validation_requires_shipping_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['shipping_address']);
    }

    /**
     * Test order with insufficient stock
     */
    public function test_order_with_insufficient_stock_fails(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'quantity' => 0,
            'is_active' => true,
        ]);

        $orderData = [
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
                'country' => 'US',
            ],
        ];

        // Mock the cart to have the out-of-stock product
        $this->mock(\App\Http\Controllers\Api\OrderController::class, function ($mock) use ($product) {
            $mock->shouldReceive('validateCartItems')
                 ->andReturn(true); // Assume validation passes
        });

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', $orderData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['cart_items.0']);
    }
}
