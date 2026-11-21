<?php
declare(strict_types=1);

namespace IMS\Services;

/**
 * Human Resources, Attendance & Payroll Wage Calculation Engine
 * Handles attendance proration, overtime bonuses, leave deductions,
 * Tax Deducted at Source (TDS), and employee payslips.
 */
class PayrollEngineService
{
    private const STANDARD_WORKING_DAYS_PER_MONTH = 26;
    private const OVERTIME_MULTIPLIER = 1.5; // 150% standard hourly wage
    private const STANDARD_DAILY_HOURS = 8;

    /**
     * Compute comprehensive monthly net salary voucher.
     *
     * @param float $baseSalary Monthly base pay
     * @param int $daysPresent Logged attendance days
     * @param float $overtimeHours Additional overtime hours worked
     * @param float $bonus Performance/festival bonus
     * @param float $advanceDeduction Salary advance loan repayments
     * @param float $taxTdsPercent Tax Deducted at Source percentage (e.g., 1% or 10%)
     * @return array Itemized payroll statement
     */
    public function computeMonthlyPayroll(
        float $baseSalary,
        int $daysPresent,
        float $overtimeHours = 0.0,
        float $bonus = 0.0,
        float $advanceDeduction = 0.0,
        float $taxTdsPercent = 1.0
    ): array {
        if ($baseSalary <= 0) {
            return [
                'base_salary' => 0.0,
                'earned_basic' => 0.0,
                'overtime_pay' => 0.0,
                'gross_salary' => 0.0,
                'net_salary' => 0.0
            ];
        }

        // Daily and hourly wage derivations
        $dailyRate = $baseSalary / self::STANDARD_WORKING_DAYS_PER_MONTH;
        $hourlyRate = $dailyRate / self::STANDARD_DAILY_HOURS;

        // Earned salary based on logged days present (capped at full month)
        $clampedDays = min(self::STANDARD_WORKING_DAYS_PER_MONTH, max(0, $daysPresent));
        $earnedBasic = $dailyRate * $clampedDays;

        // Overtime compensation
        $overtimePay = $overtimeHours * $hourlyRate * self::OVERTIME_MULTIPLIER;

        // Gross total earnings
        $grossEarnings = $earnedBasic + $overtimePay + $bonus;

        // Deductions
        $taxTds = $grossEarnings * ($taxTdsPercent / 100.0);
        $totalDeductions = $taxTds + $advanceDeduction;

        $netSalary = max(0.0, $grossEarnings - $totalDeductions);

        return [
            'base_salary' => round($baseSalary, 2),
            'working_days_target' => self::STANDARD_WORKING_DAYS_PER_MONTH,
            'days_present' => $clampedDays,
            'daily_rate' => round($dailyRate, 2),
            'hourly_rate' => round($hourlyRate, 2),
            'earned_basic' => round($earnedBasic, 2),
            'overtime_hours' => round($overtimeHours, 1),
            'overtime_pay' => round($overtimePay, 2),
            'bonus' => round($bonus, 2),
            'gross_earnings' => round($grossEarnings, 2),
            'tax_tds_percent' => $taxTdsPercent,
            'tax_tds_amount' => round($taxTds, 2),
            'advance_deduction' => round($advanceDeduction, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_salary' => round($netSalary, 2)
        ];
    }
}
