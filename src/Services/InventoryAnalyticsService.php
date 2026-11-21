<?php
declare(strict_types=1);

namespace IMS\Services;

use IMS\Core\Database;
use PDO;

/**
 * Enterprise Inventory Analytics & Mathematical Forecasting Engine
 * Computes Economic Order Quantity (EOQ), Safety Stock, Reorder Points (ROP),
 * Stock Turnover Ratios, and ABC Pareto Classification.
 */
class InventoryAnalyticsService
{
    /**
     * Compute Economic Order Quantity (EOQ) using the classical Wilson formula:
     * EOQ = sqrt((2 * Demand * OrderCost) / HoldingCost)
     *
     * @param float $annualDemand Annual units demanded (D)
     * @param float $fixedOrderCost Cost per purchase order (S)
     * @param float $annualHoldingCostPerUnit Annual cost to store one unit (H)
     * @return int Optimal purchase batch size
     */
    public function calculateEoq(float $annualDemand, float $fixedOrderCost, float $annualHoldingCostPerUnit): int
    {
        if ($annualDemand <= 0 || $fixedOrderCost <= 0 || $annualHoldingCostPerUnit <= 0) {
            return 0;
        }

        $eoq = sqrt((2 * $annualDemand * $fixedOrderCost) / $annualHoldingCostPerUnit);
        return (int) round($eoq);
    }

    /**
     * Compute Reorder Point (ROP):
     * ROP = (Daily Usage Rate * Lead Time Days) + Safety Stock
     *
     * @param float $dailyUsage Average unit consumption per day (d)
     * @param int $leadTimeDays Supplier delivery lead time in days (L)
     * @param int $safetyStock Buffer quantity for demand surges (SS)
     * @return int Quantity threshold that triggers reorder
     */
    public function calculateReorderPoint(float $dailyUsage, int $leadTimeDays, int $safetyStock = 0): int
    {
        $leadTimeDemand = $dailyUsage * $leadTimeDays;
        return (int) ceil($leadTimeDemand + $safetyStock);
    }

    /**
     * Compute Safety Stock using service factor and demand standard deviation:
     * Safety Stock = Z * sqrt(LeadTime) * StdDevDemand
     */
    public function calculateSafetyStock(float $stdDevDailyDemand, int $leadTimeDays, float $serviceFactorZ = 1.65): int
    {
        if ($leadTimeDays <= 0 || $stdDevDailyDemand <= 0) {
            return 0;
        }
        $ss = $serviceFactorZ * sqrt($leadTimeDays) * $stdDevDailyDemand;
        return (int) ceil($ss);
    }

    /**
     * Compute Inventory Turnover Ratio:
     * Turnover = Cost of Goods Sold (COGS) / Average Inventory Valuation
     */
    public function calculateInventoryTurnover(float $cogs, float $averageInventoryValue): float
    {
        if ($averageInventoryValue <= 0) {
            return 0.0;
        }
        return round($cogs / $averageInventoryValue, 2);
    }

    /**
     * Perform ABC Pareto Inventory Classification (80-15-5 Rule)
     * Category A: High value accounts for ~70-80% total value (strict control)
     * Category B: Moderate value accounts for ~15-20% total value (normal control)
     * Category C: Low value accounts for ~5-10% total value (simple control)
     *
     * @param array $items Array of items with 'id', 'name', 'quantity', 'unit_cost'
     * @return array Classified items with cumulative share and category
     */
    public function performAbcClassification(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        // 1. Compute total value for each item
        $evaluated = [];
        $totalInventoryValue = 0.0;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $cost = (float) ($item['unit_cost'] ?? ($item['cost_price'] ?? 0.0));
            $value = $qty * $cost;
            $totalInventoryValue += $value;

            $evaluated[] = array_merge($item, [
                'total_value' => $value
            ]);
        }

        if ($totalInventoryValue <= 0) {
            return array_map(fn($i) => array_merge($i, ['abc_category' => 'C', 'value_share' => 0]), $evaluated);
        }

        // 2. Sort descending by total valuation
        usort($evaluated, fn($a, $b) => $b['total_value'] <=> $a['total_value']);

        // 3. Assign cumulative shares and categories
        $cumulativeValue = 0.0;
        $classified = [];

        foreach ($evaluated as $item) {
            $cumulativeValue += $item['total_value'];
            $cumulativeShare = ($cumulativeValue / $totalInventoryValue) * 100.0;

            if ($cumulativeShare <= 80.0 || empty($classified)) {
                $category = 'A';
            } elseif ($cumulativeShare <= 95.0) {
                $category = 'B';
            } else {
                $category = 'C';
            }

            $classified[] = array_merge($item, [
                'abc_category' => $category,
                'cumulative_share_percent' => round($cumulativeShare, 2),
                'value_share_percent' => round(($item['total_value'] / $totalInventoryValue) * 100.0, 2)
            ]);
        }

        return $classified;
    }
}
