<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\Response;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test getting all products
     */
    public function test_can_get_all_products(): void
    {
        Product::factory(10)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'formatted_price',
                        'is_active',
                        'is_featured',
                        'first_image',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test getting a single product
     */
    public function test_can_get_single_product(): void
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $product->categories()->attach($category->id);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'price',
                    'formatted_price',
                    'compare_price',
                    'formatted_compare_price',
                    'discount_percentage',
                    'is_active',
                    'is_featured',
                    'is_in_stock',
                    'images',
                    'first_image',
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * Test product not found
     */
    public function test_product_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/v1/products/999');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'success' => false,
                'message' => 'Product not found.',
                'code' => 404,
            ]);
    }

    /**
     * Test creating a product as admin
     */
    public function test_admin_can_create_product(): void
    {
        $adminUser = $this->createAdminUser();
        $category = Category::factory()->create();

        $productData = [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'A test product description',
            'price' => 99.99,
            'track_quantity' => true,
            'quantity' => 100,
            'is_active' => true,
            'is_featured' => false,
            'category_ids' => [$category->id],
        ];

        $response = $this->actingAs($adminUser)
            ->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'is_active',
                    'is_featured',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 99.99,
        ]);
    }

    /**
     * Test unauthorized product creation
     */
    public function test_unauthorized_user_cannot_create_product(): void
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
        ];

        $response = $this->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
                'code' => 401,
            ]);
    }

    /**
     * Test product search
     */
    public function test_can_search_products(): void
    {
        Product::factory(5)->create(['name' => 'Test Product 1']);
        Product::factory(5)->create(['name' => 'Another Test Product']);

        $response = $this->getJson('/api/v1/products/search?q=Test');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'first_image',
                    ],
                ],
            ]);
    }

    /**
     * Test product filtering by category
     */
    public function test_can_filter_products_by_category(): void
    {
        $category = Category::factory()->create();
        Product::factory(3)->create();
        Product::factory(2)->create()->each(function ($product) use ($category) {
            $product->categories()->attach($category->id);
        });

        $response = $this->getJson("/api/v1/products?category_id={$category->id}");

        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * Test product filtering by price range
     */
    public function test_can_filter_products_by_price_range(): void
    {
        Product::factory(3)->create(['price' => 50]);
        Product::factory(2)->create(['price' => 150]);

        $response = $this->getJson('/api/v1/products?min_price=75&max_price=125');

        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * Test getting featured products
     */
    public function test_can_get_featured_products(): void
    {
        Product::factory(3)->create(['is_featured' => true]);
        Product::factory(2)->create(['is_featured' => false]);

        $response = $this->getJson('/api/v1/products/featured');

        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /**
     * Test product pagination
     */
    public function test_products_are_paginated(): void
    {
        Product::factory(25)->create();

        $response = $this->getJson('/api/v1/products?per_page=10');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to',
                    'has_more_pages',
                ],
            ]);
    }

    /**
     * Test product validation
     */
    public function test_product_validation_requires_required_fields(): void
    {
        $adminUser = $this->createAdminUser();

        $response = $this->actingAs($adminUser)
            ->postJson('/api/v1/admin/products', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'price']);
    }

    /**
     * Test product price validation
     */
    public function test_product_price_must_be_positive(): void
    {
        $adminUser = $this->createAdminUser();

        $response = $this->actingAs($adminUser)
            ->postJson('/api/v1/admin/products', [
                'name' => 'Test Product',
                'price' => -10,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['price']);
    }

    /**
     * Create admin user helper
     */
    private function createAdminUser()
    {
        return \App\Models\User::factory()->create()->assignRole('admin');
    }
}
