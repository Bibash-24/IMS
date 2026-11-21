<?php
declare(strict_types=1);

namespace IMS\Tests;

use IMS\Core\Database;

/**
 * Enterprise Unit Test Case & SQLite Test Fixture Framework
 */
abstract class TestCase
{
    protected int $assertions = 0;

    public function setUp(): void
    {
        Database::enableTestingMode();
        Database::resetTestingDatabase();
    }

    public function tearDown(): void
    {
        // Cleanup after each test run
    }

    public function getAssertionsCount(): int
    {
        return $this->assertions;
    }

    protected function assertTrue(bool $condition, string $message = 'Expected true, got false'): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new \AssertionError($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Expected false, got true'): void
    {
        $this->assertions++;
        if ($condition) {
            throw new \AssertionError($message);
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            $msg = $message ?: "Failed asserting that " . var_export($actual, true) . " equals expected " . var_export($expected, true);
            throw new \AssertionError($msg);
        }
    }

    protected function assertFloatEquals(float $expected, float $actual, float $delta = 0.001, string $message = ''): void
    {
        $this->assertions++;
        if (abs($expected - $actual) > $delta) {
            $msg = $message ?: "Failed asserting that float {$actual} equals {$expected} within delta {$delta}";
            throw new \AssertionError($msg);
        }
    }

    protected function assertGreaterThan(mixed $expectedMin, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($actual <= $expectedMin) {
            $msg = $message ?: "Failed asserting that {$actual} is greater than {$expectedMin}";
            throw new \AssertionError($msg);
        }
    }

    protected function assertContains(mixed $needle, array|string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (is_array($haystack)) {
            $found = in_array($needle, $haystack, true);
        } else {
            $found = str_contains($haystack, (string) $needle);
        }

        if (!$found) {
            $msg = $message ?: "Failed asserting that haystack contains needle";
            throw new \AssertionError($msg);
        }
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        $this->assertions++;
        if (!array_key_exists($key, $array)) {
            $msg = $message ?: "Failed asserting that array contains key '{$key}'";
            throw new \AssertionError($msg);
        }
    }
}
