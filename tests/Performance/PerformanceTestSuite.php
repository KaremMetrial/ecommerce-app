<?php

namespace Tests\Performance;

use Tests\TestCase;

class PerformanceTestSuite
{
    /**
     * Run all performance tests.
     */
    public function run_all_tests(): void
    {
        echo "Running E-Commerce Performance Test Suite...\n\n";

        $tests = [
            'API Response Times' => 'test_api_response_times',
            'Database Query Performance' => 'test_database_query_performance',
            'Cache Performance' => 'test_cache_performance',
            'Concurrent Requests' => 'test_concurrent_requests',
            'Memory Usage' => 'test_memory_usage',
            'File Upload Performance' => 'test_file_upload_performance',
            'Search Performance' => 'test_search_performance',
            'Pagination Performance' => 'test_pagination_performance',
            'API Stress Test' => 'test_api_stress',
            'Database Benchmarks' => 'test_database_benchmarks',
            'Load Testing - Light' => 'test_load_performance',
            'Load Testing - Moderate' => 'test_load_performance',
            'Load Testing - Heavy' => 'test_load_performance',
            'Memory Under Load' => 'test_memory_under_load',
            'Database Pool Under Load' => 'test_database_pool_under_load',
            'Cache Under Load' => 'test_cache_under_load',
        ];

        $results = [];
        $passed = 0;
        $failed = 0;

        foreach ($tests as $testName => $testMethod) {
            echo "Running {$testName}...\n";

            try {
                $test = new ApiPerformanceTest();
                $test->$testMethod();
                $results[] = [
                    'test' => $testName,
                    'status' => 'PASSED',
                    'message' => 'All assertions passed',
                ];
                $passed++;
            } catch (\Exception $e) {
                $results[] = [
                    'test' => $testName,
                    'status' => 'FAILED',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ];
                $failed++;
            }

            echo str_repeat('-', 50) . "\n";
        }

        // Summary
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "PERFORMANCE TEST SUITE SUMMARY\n";
        echo str_repeat('=', 50) . "\n";
        echo "Total Tests: " . ($passed + $failed) . "\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Success Rate: " . round(($passed / ($passed + $failed)) * 100, 2) . "%\n";
        echo str_repeat('=', 50) . "\n\n";

        // Detailed results
        if ($failed > 0) {
            echo "FAILED TESTS:\n";
            foreach ($results as $result) {
                if ($result['status'] === 'FAILED') {
                    echo "❌ {$result['test']}: {$result['message']}\n";
                    if (isset($result['trace'])) {
                        echo "Trace: {$result['trace']}\n";
                    }
                }
            }
        }

        echo "\nPerformance testing completed!\n";
        echo "Results have been logged to the application logs.\n";

        // Exit with appropriate code
        exit($failed > 0 ? 1 : 0);
    }
}
