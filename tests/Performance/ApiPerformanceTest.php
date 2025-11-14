<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test API response times under load.
     */
    public function test_api_response_times(): void
    {
        $endpoints = [
            '/api/v1/products',
            '/api/v1/categories',
            '/api/v1/orders',
            '/api/v1/cart',
        ];

        foreach ($endpoints as $endpoint) {
            $responseTime = $this->measureResponseTime($endpoint);

            // Assert reasonable response times
            $this->assertLessThan($responseTime, 1000, "API endpoint {$endpoint} response time should be under 1 second");

            Log::info("Performance Test - {$endpoint}: {$responseTime}ms");
        }
    }

    /**
     * Test database query performance.
     */
    public function test_database_query_performance(): void
    {
        // Create test data
        $users = \App\Models\User::factory(100)->create();
        $products = \App\Models\Product::factory(50)->create();
        $categories = \App\Models\Category::factory(10)->create();

        // Measure complex query performance
        $startTime = microtime(true);

        // Complex join query
        $results = DB::table('users')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('users.name', 'orders.total', 'products.name', DB::raw('COUNT(order_items.id) as item_count'))
            ->groupBy('users.id')
            ->having('item_count', '>', 5)
            ->get();

        $queryTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan($queryTime, 500, "Complex database query should execute in under 500ms");

        Log::info("Database Performance Test: {$queryTime}ms for " . count($results) . " results");
    }

    /**
     * Test cache performance.
     */
    public function test_cache_performance(): void
    {
        $cache = app('cache');

        // Test cache write performance
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $cache->put("test_key_{$i}", "test_value_{$i}", 3600);
        }
        $writeTime = (microtime(true) - $startTime) * 1000;

        // Test cache read performance
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $cache->get("test_key_{$i}");
        }
        $readTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan($writeTime, 1000, "Cache write should complete in under 1 second");
        $this->assertLessThan($readTime, 500, "Cache read should complete in under 500ms");

        Log::info("Cache Performance Test - Write: {$writeTime}ms, Read: {$readTime}ms");
    }

    /**
     * Test concurrent request handling.
     */
    public function test_concurrent_requests(): void
    {
        $concurrentRequests = 10;
        $endpoint = '/api/v1/products';

        $startTime = microtime(true);
        $promises = [];

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $promises[] = Http::async('get', url($endpoint));
        }

        // Wait for all requests to complete
        $results = [];
        foreach ($promises as $promise) {
            $results[] = $promise->wait();
        }

        $totalTime = (microtime(true) - $startTime) * 1000;

        // Assert all requests were successful
        foreach ($results as $result) {
            $this->assertTrue($result->successful(), "Concurrent request should be successful");
        }

        $this->assertLessThan($totalTime, 5000, "10 concurrent requests should complete in under 5 seconds");

        Log::info("Concurrent Requests Test: {$totalTime}ms for {$concurrentRequests} requests");
    }

    /**
     * Test memory usage during operations.
     */
    public function test_memory_usage(): void
    {
        $initialMemory = memory_get_usage(true);

        // Perform memory-intensive operation
        $largeArray = [];
        for ($i = 0; $i < 10000; $i++) {
            $largeArray["item_{$i}"] = str_repeat('x', 100);
        }

        $peakMemory = memory_get_peak_usage(true);
        $finalMemory = memory_get_usage(true);

        $memoryUsed = $finalMemory['real'] - $initialMemory['real'];

        $this->assertLessThan($memoryUsed / 1024 / 1024, 50, "Memory usage should be under 50MB");
        $this->assertLessThan($peakMemory['real'] / 1024 / 1024, 100, "Peak memory should be under 100MB");

        Log::info("Memory Usage Test: " . round($memoryUsed / 1024 / 1024, 2) . "MB used, " . round($peakMemory['real'] / 1024 / 1024, 2) . "MB peak");
    }

    /**
     * Test file upload performance.
     */
    public function test_file_upload_performance(): void
    {
        $fileSizes = [1024, 5120, 10240]; // 1KB, 5KB, 10KB

        foreach ($fileSizes as $size) {
            $startTime = microtime(true);

            $content = str_repeat('x', $size);
            $filename = "test_file_{$size}.txt";

            $response = $this->postJson('/api/v1/upload', [
                'file' => uploadedFile($content, $filename, 'text/plain'),
            ]);

            $uploadTime = (microtime(true) - $startTime) * 1000;

            $this->assertTrue($response->successful(), "File upload should be successful");
            $this->assertLessThan($uploadTime, 5000, "File upload of {$size} bytes should complete in under 5 seconds");

            Log::info("File Upload Test - {$size} bytes: {$uploadTime}ms");
        }
    }

    /**
     * Test search performance.
     */
    public function test_search_performance(): void
    {
        $searchTerms = ['laptop', 'phone', 'book', 'electronics'];

        foreach ($searchTerms as $term) {
            $startTime = microtime(true);

            $response = Http::get('/api/v1/products/search', ['q' => $term]);

            $searchTime = (microtime(true) - $startTime) * 1000;

            $this->assertTrue($response->successful(), "Search should be successful");
            $this->assertLessThan($searchTime, 2000, "Search for '{$term}' should complete in under 2 seconds");

            Log::info("Search Performance Test - '{$term}': {$searchTime}ms");
        }
    }

    /**
     * Measure API response time.
     */
    private function measureResponseTime(string $endpoint): float
    {
        $startTime = microtime(true);

        $response = Http::get($endpoint);

        $responseTime = (microtime(true) - $startTime) * 1000;

        $this->assertTrue($response->successful(), "API endpoint should respond successfully");

        return $responseTime;
    }

    /**
     * Test pagination performance.
     */
    public function test_pagination_performance(): void
    {
        $pageSizes = [10, 25, 50, 100];

        foreach ($pageSizes as $size) {
            $startTime = microtime(true);

            $response = Http::get('/api/v1/products', ['per_page' => $size, 'page' => 1]);

            $paginationTime = (microtime(true) - $startTime) * 1000;

            $this->assertTrue($response->successful(), "Pagination should be successful");
            $this->assertLessThan($paginationTime, 1500, "Pagination with {$size} items should complete in under 1.5 seconds");

            Log::info("Pagination Performance Test - Size {$size}: {$paginationTime}ms");
        }
    }

    /**
     * Stress test API endpoints.
     */
    public function test_api_stress(): void
    {
        $endpoint = '/api/v1/products';
        $requests = 100;
        $concurrency = 5;

        $startTime = microtime(true);
        $errors = 0;
        $successes = 0;

        for ($i = 0; $i < $requests; $i += $concurrency) {
            $promises = [];

            for ($j = 0; $j < $concurrency; $j++) {
                $promises[] = Http::async('get', url($endpoint));
            }

            foreach ($promises as $promise) {
                $result = $promise->wait();
                if ($result->successful()) {
                    $successes++;
                } else {
                    $errors++;
                }
            }
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        $successRate = ($successes / $requests) * 100;

        $this->assertGreaterThan($successRate, 95, "Success rate should be above 95%");
        $this->assertLessThan($totalTime, 30000, "Stress test should complete in under 30 seconds");

        Log::info("Stress Test Results: {$requests} requests, {$successRate}% success rate, {$totalTime}ms total time");
    }

    /**
     * Benchmark database operations.
     */
    public function test_database_benchmarks(): void
    {
        $operations = [
            'insert' => function() {
                return DB::table('test_bench')->insert(['data' => str_repeat('x', 100)]);
            },
            'select' => function() {
                return DB::table('test_bench')->where('id', '>', 0)->get();
            },
            'update' => function() {
                return DB::table('test_bench')->where('id', 1)->update(['data' => str_repeat('y', 100)]);
            },
            'delete' => function() {
                return DB::table('test_bench')->where('id', '>', 100)->delete();
            },
        ];

        foreach ($operations as $name => $operation) {
            $iterations = 100;

            $startTime = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $operation();
            }
            $totalTime = (microtime(true) - $startTime) * 1000;
            $avgTime = $totalTime / $iterations;

            $this->assertLessThan($avgTime, 10, "Average {$name} operation should be under 10ms");

            Log::info("Database Benchmark - {$name}: {$avgTime}ms average ({$iterations} iterations)");
        }
    }
}
