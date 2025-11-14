<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices, gadgets, and accessories',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'meta' => [
                    'title' => 'Electronics - Best Deals on Tech',
                    'description' => 'Find the latest electronics and gadgets at great prices',
                    'keywords' => 'electronics, gadgets, tech, devices'
                ],
                'children' => [
                    [
                        'name' => 'Smartphones',
                        'slug' => 'smartphones',
                        'description' => 'Latest smartphones and mobile devices',
                        'is_active' => true,
                        'is_featured' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Laptops',
                        'slug' => 'laptops',
                        'description' => 'Professional and gaming laptops',
                        'is_active' => true,
                        'is_featured' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Tablets',
                        'slug' => 'tablets',
                        'description' => 'Tablets for work and entertainment',
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Audio',
                        'slug' => 'audio',
                        'description' => 'Headphones, speakers, and audio equipment',
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => 4,
                    ],
                ]
            ],
            [
                'name' => 'Clothing',
                'slug' => 'clothing',
                'description' => 'Fashion and apparel for all ages',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'meta' => [
                    'title' => 'Clothing - Latest Fashion Trends',
                    'description' => 'Shop the latest fashion trends and styles',
                    'keywords' => 'clothing, fashion, apparel, style'
                ],
                'children' => [
                    [
                        'name' => 'Men\'s Clothing',
                        'slug' => 'mens-clothing',
                        'description' => 'Clothing for men',
                        'is_active' => true,
                        'is_featured' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Women\'s Clothing',
                        'slug' => 'womens-clothing',
                        'description' => 'Clothing for women',
                        'is_active' => true,
                        'is_featured' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Kids\' Clothing',
                        'slug' => 'kids-clothing',
                        'description' => 'Clothing for children',
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Everything for your home and garden',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'meta' => [
                    'title' => 'Home & Garden - Make Your Space Beautiful',
                    'description' => 'Transform your home and garden with our products',
                    'keywords' => 'home, garden, furniture, decor'
                ],
                'children' => [
                    [
                        'name' => 'Furniture',
                        'slug' => 'furniture',
                        'description' => 'Quality furniture for every room',
                        'is_active' => true,
                        'is_featured' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Kitchen',
                        'slug' => 'kitchen',
                        'description' => 'Kitchen appliances and accessories',
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Garden',
                        'slug' => 'garden',
                        'description' => 'Garden tools and supplies',
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4,
                'meta' => [
                    'title' => 'Sports & Outdoors - Gear Up for Adventure',
                    'description' => 'Find the best sports equipment and outdoor gear',
                    'keywords' => 'sports, outdoors, fitness, adventure'
                ],
                'children' => [
                    [
                        'name' => 'Fitness',
                        'slug' => 'fitness',
                        'description' => 'Fitness equipment and accessories',
                        'is_active' => true,
                        'is_featured' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Outdoor Recreation',
                        'slug' => 'outdoor-recreation',
                        'description' => 'Outdoor gear and equipment',
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => 2,
                    ],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::create($categoryData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                Category::create($childData);
            }
        }
    }
}
