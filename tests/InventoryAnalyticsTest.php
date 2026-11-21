<?php
declare(strict_types=1);

namespace IMS\Tests;

use IMS\Services\InventoryAnalyticsService;

class InventoryAnalyticsTest extends TestCase
{
    private InventoryAnalyticsService $analytics;

    public function setUp(): void
    {
        parent::setUp();
        $this->analytics = new InventoryAnalyticsService();
    }

    public function testEoqCalculationStandardValues(): void
    {
        // Demand = 10,000, Order Cost = 50, Holding Cost = 4
        // EOQ = sqrt((2 * 10000 * 50) / 4) = sqrt(250000) = 500
        $eoq = $this->analytics->calculateEoq(10000.0, 50.0, 4.0);
        $this->assertEquals(500, $eoq);
    }

    public function testEoqReturnsZeroOnInvalidInputs(): void
    {
        $this->assertEquals(0, $this->analytics->calculateEoq(0, 50, 4));
        $this->assertEquals(0, $this->analytics->calculateEoq(10000, 0, 4));
        $this->assertEquals(0, $this->analytics->calculateEoq(10000, 50, -2));
    }

    public function testReorderPointWithSafetyStock(): void
    {
        // Daily Usage = 15, Lead Time = 5 days, Safety Stock = 20
        // Expected ROP = (15 * 5) + 20 = 75 + 20 = 95
        $rop = $this->analytics->calculateReorderPoint(15.0, 5, 20);
        $this->assertEquals(95, $rop);
    }

    public function testSafetyStockCalculation(): void
    {
        // StdDev = 4, Lead Time = 9 days, Service Factor = 1.65 (95% service level)
        // SS = 1.65 * sqrt(9) * 4 = 1.65 * 3 * 4 = 19.8 -> ceil = 20
        $ss = $this->analytics->calculateSafetyStock(4.0, 9, 1.65);
        $this->assertEquals(20, $ss);
    }

    public function testInventoryTurnoverRatio(): void
    {
        // COGS = 500,000, Average Inventory = 125,000 -> Turnover = 4.0
        $turnover = $this->analytics->calculateInventoryTurnover(500000.0, 125000.0);
        $this->assertFloatEquals(4.0, $turnover);
        $this->assertEquals(0.0, $this->analytics->calculateInventoryTurnover(500000.0, 0.0));
    }

    public function testAbcParetoClassification(): void
    {
        $items = [
            ['id' => 1, 'name' => 'High Value Enterprise Server', 'quantity' => 10, 'unit_cost' => 80000.0], // 800,000 (80%)
            ['id' => 2, 'name' => 'Mid Range Monitor', 'quantity' => 20, 'unit_cost' => 7500.0],             // 150,000 (15%)
            ['id' => 3, 'name' => 'USB Flash Drives', 'quantity' => 50, 'unit_cost' => 1000.0]              //  50,000 (5%)
        ];

        $classified = $this->analytics->performAbcClassification($items);

        $this->assertEquals('A', $classified[0]['abc_category']);
        $this->assertEquals('B', $classified[1]['abc_category']);
        $this->assertEquals('C', $classified[2]['abc_category']);
        $this->assertGreaterThan(70.0, $classified[0]['value_share_percent']);
    }
}
