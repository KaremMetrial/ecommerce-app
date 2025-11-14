<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test application performance under simulated load.
     */
    public function test_load_performance(): void
    {
        $scenarios = [
            'light_load' => ['users' => 10, 'duration' => 60],
            'moderate_load' => ['users' => 50, 'duration' => 60],
            'heavy_load' => ['users' => 100, 'duration' => 60],
        ];

        foreach ($scenarios as $scenario => $config) {
            $this->runLoadScenario($scenario, $config);
        }
    }

    /**
     * Run individual load scenario.
     */
    private function runLoadScenario(string $scenario, array $config): void
    {
        Log::info("Starting load test: {$scenario}");

        $results = $this->simulateLoad($config['users'], $config['duration']);

        $this->assertLoadResults($scenario, $results, $config);

        Log::info("Load test completed: {$scenario}");
    }

    /**
     * Simulate load on the application.
     */
    private function simulateLoad(int $concurrentUsers, int $duration): array
    {
        $endpoint = '/api/v1/products';
        $results = [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'avg_response_time' => 0,
            'max_response_time' => 0,
            'min_response_time' => 0,
            'errors' => [],
        ];

        $startTime = microtime(true);
        $endTime = $startTime + $duration;

        // Create concurrent requests
        $promises = [];
        for ($i = 0; $i < $concurrentUsers; $i++) {
            for ($j = 0; $j < 10; $j++) { // Each user makes 10 requests
                $promises[] = Http::async('get', url($endpoint));
            }
        }

        // Execute requests and collect results
        while (microtime(true) < $endTime) {
            foreach ($promises as $promise) {
                $result = $promise->wait();
                $results['total_requests']++;

                if ($result->successful()) {
                    $results['successful_requests']++;
                    $responseTime = $result->responseTime() ?? 0;
                    $results['avg_response_time'] += $responseTime;
                    $results['max_response_time'] = max($results['max_response_time'], $responseTime);
                    $results['min_response_time'] = min($results['min_response_time'], $responseTime);
                } else {
                    $results['failed_requests']++;
                    $results['errors'][] = [
                        'response' => $result->body(),
                        'status' => $result->status(),
                    ];
                }
            }

            usleep(100000); // 100ms delay between requests
        }

        // Calculate averages
        if ($results['successful_requests'] > 0) {
            $results['avg_response_time'] = $results['avg_response_time'] / $results['successful_requests'];
        }

        return $results;
    }

    /**
     * Assert load test results.
     */
    private function assertLoadResults(string $scenario, array $results, array $config): void
    {
        $successRate = ($results['successful_requests'] / $results['total_requests']) * 100;

        // Assert success rate is above threshold
        $this->assertGreaterThan($successRate, 95, "Success rate should be above 95% for {$scenario}");

        // Assert average response time is below threshold
        $maxAvgResponseTime = match (true) {
            'light_load' => 500,   // 500ms for light load
            'moderate_load' => 1000, // 1s for moderate load
            'heavy_load' => 2000,   // 2s for heavy load
        };

        $this->assertLessThan($results['avg_response_time'], $maxAvgResponseTime[$scenario], "Average response time should be under {$maxAvgResponseTime[$scenario]}ms for {$scenario}");

        // Assert no 500 errors
        $errorStatuses = array_filter($results['errors'], fn($error) => $error['status'] === 500);
        $this->assertEmpty($errorStatuses, "Should have no 500 errors for {$scenario}");

        Log::info("Load Test Results - {$scenario}: " . json_encode([
            'scenario' => $scenario,
            'config' => $config,
            'results' => $results,
            'success_rate' => $successRate,
            'passed' => $successRate >= 95 && $results['avg_response_time'] <= $maxAvgResponseTime[$scenario],
        ]));
    }

    /**
     * Test memory usage under load.
     */
    public function test_memory_under_load(): void
    {
        $initialMemory = memory_get_usage(true);
        $peakMemory = $initialMemory;

        // Simulate memory-intensive operations
        $largeArrays = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeArrays[] = array_fill(0, 1000, str_repeat('x', 1000));
        }

        $currentPeak = memory_get_peak_usage(true);
        $memoryIncrease = ($currentPeak['real'] - $peakMemory['real']) / 1024 / 1024;

        $this->assertLessThan($memoryIncrease, 50, "Memory increase should be under 50MB under load");

        Log::info("Memory Under Load Test: " . round($memoryIncrease, 2) . "MB increase");
    }

    /**
     * Test database connection pool under load.
     */
    public function test_database_pool_under_load(): void
    {
        $connections = [];
        $maxConnections = 20;

        // Simulate rapid database connections
        for ($i = 0; $i < $maxConnections; $i++) {
            $startTime = microtime(true);
            DB::select('SELECT 1'); // Simple query to create connection
            $connectionTime = (microtime(true) - $startTime) * 1000;
            $connections[] = $connectionTime;
        }

        $avgConnectionTime = array_sum($connections) / count($connections);
        $maxConnectionTime = max($connections);

        $this->assertLessThan($avgConnectionTime, 100, "Average connection time should be under 100ms");
        $this->assertLessThan($maxConnectionTime, 500, "Max connection time should be under 500ms");

        Log::info("Database Pool Test: Avg connection time {$avgConnectionTime}ms, Max {$maxConnectionTime}ms");
    }

    /**
     * Test cache performance under load.
     */
    public function test_cache_under_load(): void
    {
        $cache = app('cache');
        $operations = 1000;

        $startTime = microtime(true);

        // Simulate rapid cache operations
        for ($i = 0; $i < $operations; $i++) {
            $cache->put("test_key_{$i}", "test_value_{$i}", 3600);
            $cache->get("test_key_{$i}");
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        $avgOperationTime = $totalTime / $operations;

        $this->assertLessThan($avgOperationTime, 10, "Average cache operation should be under 10ms");

        Log::info("Cache Under Load Test: {$avgOperationTime}ms average operation time");
    }
}
