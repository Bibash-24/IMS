<?php
declare(strict_types=1);

/**
 * Enterprise Standalone Automated Test Harness Runner for IMS
 * Runs on zero-dependency SQLite memory engine.
 */

require_once __DIR__ . '/../src/autoload.php';
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/InventoryAnalyticsTest.php';
require_once __DIR__ . '/PosCheckoutTest.php';
require_once __DIR__ . '/PurchaseOrderTest.php';
require_once __DIR__ . '/AccountsLedgerTest.php';
require_once __DIR__ . '/SecurityTest.php';
require_once __DIR__ . '/BarcodeQrTest.php';
require_once __DIR__ . '/PayrollEngineTest.php';

use IMS\Tests\InventoryAnalyticsTest;
use IMS\Tests\PosCheckoutTest;
use IMS\Tests\PurchaseOrderTest;
use IMS\Tests\AccountsLedgerTest;
use IMS\Tests\SecurityTest;
use IMS\Tests\BarcodeQrTest;
use IMS\Tests\PayrollEngineTest;

$startTime = microtime(true);

echo "====================================================================\n";
echo "   Enterprise Inventory Management System (IMS) — Test Harness    \n";
echo "====================================================================\n\n";

$testSuites = [
    'InventoryAnalyticsTest' => new InventoryAnalyticsTest(),
    'PosCheckoutTest' => new PosCheckoutTest(),
    'PurchaseOrderTest' => new PurchaseOrderTest(),
    'AccountsLedgerTest' => new AccountsLedgerTest(),
    'SecurityTest' => new SecurityTest(),
    'BarcodeQrTest' => new BarcodeQrTest(),
    'PayrollEngineTest' => new PayrollEngineTest()
];

$totalTests = 0;
$totalAssertions = 0;
$failedTests = 0;
$suiteCount = count($testSuites);

foreach ($testSuites as $suiteName => $suite) {
    echo "Running Suite: {$suiteName}\n";
    $methods = get_class_methods($suite);
    $testMethods = array_filter($methods, fn($m) => str_starts_with($m, 'test'));

    foreach ($testMethods as $testMethod) {
        $totalTests++;
        try {
            $suite->setUp();
            $suite->$testMethod();
            $suite->tearDown();
            echo "  [PASS] {$testMethod}\n";
        } catch (\Throwable $e) {
            $failedTests++;
            echo "  [FAIL] {$testMethod}\n";
            echo "         Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
    }
    $totalAssertions += $suite->getAssertionsCount();
    echo "\n";
}

$executionTime = round((microtime(true) - $startTime) * 1000, 2);
$passRate = $totalTests > 0 ? round((($totalTests - $failedTests) / $totalTests) * 100, 1) : 0;

echo "====================================================================\n";
echo "                         TEST RESULTS SUMMARY                       \n";
echo "====================================================================\n";
echo "Suites Executed : {$suiteCount}\n";
echo "Total Tests Run : {$totalTests}\n";
echo "Total Assertions: {$totalAssertions}\n";
echo "Passed Tests    : " . ($totalTests - $failedTests) . "\n";
echo "Failed Tests    : {$failedTests}\n";
echo "Execution Time  : {$executionTime} ms\n";
echo "Pass Rate       : {$passRate}%\n\n";

if ($failedTests === 0) {
    echo "ALL TESTS PASSED WITH ZERO FAILURES.\n";
    exit(0);
} else {
    echo "TEST HARNESS COMPLETED WITH FAILURES.\n";
    exit(1);
}
