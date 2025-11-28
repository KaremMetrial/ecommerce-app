<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = $product->price;

        return [
            'cart_id' => Cart::factory(),
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'product_data' => [
                'name' => $product->name,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'image' => $product->first_image,
            ],
        ];
    }

    /**
     * Indicate that the cart item should have a variant.
     */
    public function withVariant(): static
    {
        return $this->state(function (array $attributes) {
            $product = Product::find($attributes['product_id']);
            $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

            return array_merge($attributes, [
                'product_variant_id' => $variant->id,
                'unit_price' => $variant->price,
                'total_price' => $variant->price * $attributes['quantity'],
                'product_data' => array_merge($attributes['product_data'], [
                    'variant_name' => $variant->name,
                    'variant_sku' => $variant->sku,
                    'variant_attributes' => $variant->attributes,
                    'image' => $variant->image ?? $attributes['product_data']['image'],
                ]),
            ]);
        });
    }

    /**
     * Create a cart item for a specific cart.
     */
    public function forCart(Cart $cart): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'cart_id' => $cart->id,
        ]));
    }

    /**
     * Create a cart item for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(function (array $attributes) use ($product) {
            $quantity = $attributes['quantity'];
            $unitPrice = $product->price;

            return array_merge($attributes, [
                'product_id' => $product->id,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'product_data' => [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'slug' => $product->slug,
                    'image' => $product->first_image,
                ],
            ]);
        });
    }
}
