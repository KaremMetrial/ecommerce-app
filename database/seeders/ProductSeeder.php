<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $categories = Category::all()->keyBy('slug');

        $products = [
            // Electronics
            [
                'name' => 'iPhone 15 Pro Max',
                'slug' => 'iphone-15-pro-max',
                'description' => 'The iPhone 15 Pro Max features a stunning titanium design, A17 Pro chip with GPU, and pro camera system. Experience the future of mobile technology with advanced features and exceptional performance.',
                'short_description' => 'Latest iPhone with titanium design and A17 Pro chip',
                'price' => 1199.00,
                'compare_price' => 1299.00,
                'cost_price' => 900.00,
                'track_quantity' => true,
                'quantity' => 50,
                'min_stock_level' => 10,
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'weight' => 0.29,
                'dimensions' => [
                    'length' => 15.98,
                    'width' => 7.67,
                    'height' => 0.81
                ],
                'images' => [
                    'https://example.com/images/iphone-15-pro-max-1.jpg',
                    'https://example.com/images/iphone-15-pro-max-2.jpg',
                    'https://example.com/images/iphone-15-pro-max-3.jpg'
                ],
                'attributes' => [
                    'brand' => 'Apple',
                    'model' => 'iPhone 15 Pro Max',
                    'storage' => '256GB',
                    'color' => 'Natural Titanium',
                    'display' => '6.7-inch Super Retina XDR',
                    'camera' => '48MP Main + 12MP Ultra Wide + 12MP Telephoto'
                ],
                'meta' => [
                    'title' => 'iPhone 15 Pro Max - Buy Now',
                    'description' => 'Get the iPhone 15 Pro Max with titanium design and A17 Pro chip',
                    'keywords' => 'iPhone, Apple, smartphone, titanium'
                ],
                'published_at' => now(),
                'category_slugs' => ['smartphones']
            ],
            [
                'name' => 'MacBook Pro 16"',
                'slug' => 'macbook-pro-16',
                'description' => 'The MacBook Pro 16" with M3 Pro chip delivers exceptional performance for professionals. Features stunning Liquid Retina XDR display, advanced camera system, and all-day battery life.',
                'short_description' => 'Professional laptop with M3 Pro chip and stunning display',
                'price' => 2499.00,
                'compare_price' => 2799.00,
                'cost_price' => 1800.00,
                'track_quantity' => true,
                'quantity' => 25,
                'min_stock_level' => 5,
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'weight' => 2.15,
                'dimensions' => [
                    'length' => 35.57,
                    'width' => 24.81,
                    'height' => 1.55
                ],
                'images' => [
                    'https://example.com/images/macbook-pro-16-1.jpg',
                    'https://example.com/images/macbook-pro-16-2.jpg'
                ],
                'attributes' => [
                    'brand' => 'Apple',
                    'model' => 'MacBook Pro 16"',
                    'processor' => 'M3 Pro',
                    'memory' => '18GB',
                    'storage' => '512GB SSD',
                    'display' => '16.2-inch Liquid Retina XDR',
                    'color' => 'Space Gray'
                ],
                'meta' => [
                    'title' => 'MacBook Pro 16" - Professional Laptop',
                    'description' => 'Professional MacBook Pro with M3 Pro chip for demanding work',
                    'keywords' => 'MacBook, Apple, laptop, professional'
                ],
                'published_at' => now(),
                'category_slugs' => ['laptops']
            ],
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'slug' => 'sony-wh-1000xm5-headphones',
                'description' => 'Industry-leading noise canceling with Dual Noise Sensor technology. Up to 30-hour battery life, exceptional sound quality, and multipoint connection.',
                'short_description' => 'Premium noise-canceling headphones with 30-hour battery',
                'price' => 399.00,
                'compare_price' => 449.00,
                'cost_price' => 280.00,
                'track_quantity' => true,
                'quantity' => 75,
                'min_stock_level' => 15,
                'is_active' => true,
                'is_featured' => false,
                'is_digital' => false,
                'weight' => 0.25,
                'images' => [
                    'https://example.com/images/sony-wh1000xm5-1.jpg',
                    'https://example.com/images/sony-wh1000xm5-2.jpg'
                ],
                'attributes' => [
                    'brand' => 'Sony',
                    'model' => 'WH-1000XM5',
                    'type' => 'Over-ear',
                    'noise_canceling' => 'Yes',
                    'battery_life' => '30 hours',
                    'connectivity' => 'Bluetooth 5.2',
                    'color' => 'Black'
                ],
                'meta' => [
                    'title' => 'Sony WH-1000XM5 - Premium Headphones',
                    'description' => 'Premium noise-canceling headphones with exceptional sound',
                    'keywords' => 'Sony, headphones, noise canceling, wireless'
                ],
                'published_at' => now(),
                'category_slugs' => ['audio']
            ],
            // Clothing
            [
                'name' => 'Men\'s Premium Cotton T-Shirt',
                'slug' => 'mens-premium-cotton-t-shirt',
                'description' => 'Premium quality 100% organic cotton t-shirt. Soft, comfortable, and durable. Perfect for everyday wear with a modern fit.',
                'short_description' => 'Premium organic cotton t-shirt for men',
                'price' => 29.99,
                'compare_price' => 39.99,
                'cost_price' => 15.00,
                'track_quantity' => true,
                'quantity' => 200,
                'min_stock_level' => 20,
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'weight' => 0.18,
                'images' => [
                    'https://example.com/images/mens-tshirt-1.jpg',
                    'https://example.com/images/mens-tshirt-2.jpg'
                ],
                'attributes' => [
                    'brand' => 'Premium Basics',
                    'material' => '100% Organic Cotton',
                    'fit' => 'Regular Fit',
                    'care' => 'Machine Washable',
                    'available_sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'available_colors' => ['White', 'Black', 'Navy', 'Gray']
                ],
                'meta' => [
                    'title' => 'Men\'s Premium Cotton T-Shirt',
                    'description' => 'Comfortable organic cotton t-shirt for everyday wear',
                    'keywords' => 't-shirt, cotton, men, clothing, organic'
                ],
                'published_at' => now(),
                'category_slugs' => ['mens-clothing']
            ],
            [
                'name' => 'Women\'s Elegant Summer Dress',
                'slug' => 'womens-elegant-summer-dress',
                'description' => 'Beautiful summer dress made from lightweight, breathable fabric. Elegant design perfect for special occasions or casual outings.',
                'short_description' => 'Elegant summer dress for women',
                'price' => 79.99,
                'compare_price' => 99.99,
                'cost_price' => 45.00,
                'track_quantity' => true,
                'quantity' => 80,
                'min_stock_level' => 10,
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'weight' => 0.35,
                'images' => [
                    'https://example.com/images/womens-dress-1.jpg',
                    'https://example.com/images/womens-dress-2.jpg'
                ],
                'attributes' => [
                    'brand' => 'Elegant Wear',
                    'material' => 'Polyester Blend',
                    'style' => 'A-line',
                    'sleeve_length' => 'Short Sleeve',
                    'length' => 'Knee-length',
                    'available_sizes' => ['XS', 'S', 'M', 'L', 'XL'],
                    'available_colors' => ['Floral Print', 'Solid Blue', 'Solid Pink']
                ],
                'meta' => [
                    'title' => 'Women\'s Elegant Summer Dress',
                    'description' => 'Beautiful summer dress for special occasions',
                    'keywords' => 'dress, summer, women, elegant, floral'
                ],
                'published_at' => now(),
                'category_slugs' => ['womens-clothing']
            ],
            // Home & Garden
            [
                'name' => 'Modern Office Chair',
                'slug' => 'modern-office-chair',
                'description' => 'Ergonomic office chair with lumbar support, adjustable height, and breathable mesh back. Perfect for long working hours.',
                'short_description' => 'Ergonomic office chair with lumbar support',
                'price' => 299.99,
                'compare_price' => 399.99,
                'cost_price' => 180.00,
                'track_quantity' => true,
                'quantity' => 40,
                'min_stock_level' => 8,
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'weight' => 15.5,
                'dimensions' => [
                    'length' => 26.0,
                    'width' => 26.0,
                    'height' => 42.5
                ],
                'images' => [
                    'https://example.com/images/office-chair-1.jpg',
                    'https://example.com/images/office-chair-2.jpg'
                ],
                'attributes' => [
                    'brand' => 'Comfort Seating',
                    'material' => 'Mesh and Fabric',
                    'adjustable_height' => true,
                    'lumbar_support' => true,
                    'armrests' => 'Adjustable',
                    'swivel' => true,
                    'wheels' => true,
                    'color' => 'Black'
                ],
                'meta' => [
                    'title' => 'Modern Office Chair - Ergonomic Design',
                    'description' => 'Comfortable ergonomic office chair for productivity',
                    'keywords' => 'office chair, ergonomic, furniture, work'
                ],
                'published_at' => now(),
                'category_slugs' => ['furniture']
            ],
            // Sports & Outdoors
            [
                'name' => 'Professional Yoga Mat',
                'slug' => 'professional-yoga-mat',
                'description' => 'High-quality yoga mat with superior grip and cushioning. Non-slip surface perfect for all types of yoga and exercise.',
                'short_description' => 'Professional non-slip yoga mat',
                'price' => 49.99,
                'compare_price' => 69.99,
                'cost_price' => 25.00,
                'track_quantity' => true,
                'quantity' => 150,
                'min_stock_level' => 25,
                'is_active' => true,
                'is_featured' => false,
                'is_digital' => false,
                'weight' => 1.2,
                'dimensions' => [
                    'length' => 183.0,
                    'width' => 61.0,
                    'height' => 0.6
                ],
                'images' => [
                    'https://example.com/images/yoga-mat-1.jpg',
                    'https://example.com/images/yoga-mat-2.jpg'
                ],
                'attributes' => [
                    'brand' => 'Fitness Pro',
                    'material' => 'TPE Eco-Friendly',
                    'thickness' => '6mm',
                    'length' => '183cm',
                    'width' => '61cm',
                    'texture' => 'Non-slip',
                    'care' => 'Easy to Clean',
                    'available_colors' => ['Purple', 'Blue', 'Green', 'Pink']
                ],
                'meta' => [
                    'title' => 'Professional Yoga Mat - Non-Slip Surface',
                    'description' => 'High-quality yoga mat for all exercise types',
                    'keywords' => 'yoga mat, fitness, exercise, non-slip'
                ],
                'published_at' => now(),
                'category_slugs' => ['fitness']
            ],
        ];

        foreach ($products as $productData) {
            $categorySlugs = $productData['category_slugs'] ?? [];
            unset($productData['category_slugs']);

            $product = Product::create($productData);

            // Attach categories
            foreach ($categorySlugs as $slug) {
                if (isset($categories[$slug])) {
                    $product->categories()->attach($categories[$slug]->id);
                }
            }
        }
    }
}
