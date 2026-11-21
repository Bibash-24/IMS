<?php
declare(strict_types=1);

namespace IMS\Tests;

use IMS\Services\PayrollEngineService;

class PayrollEngineTest extends TestCase
{
    private PayrollEngineService $payroll;

    public function setUp(): void
    {
        parent::setUp();
        $this->payroll = new PayrollEngineService();
    }

    public function testFullMonthAttendancePayroll(): void
    {
        // Base = 52,000, 26 days present (100%), 0 overtime, 0 bonus
        // Daily rate = 52,000 / 26 = 2,000
        // TDS 1% = 520 -> Net = 51,480
        $slip = $this->payroll->computeMonthlyPayroll(52000.0, 26, 0.0, 0.0, 0.0, 1.0);

        $this->assertFloatEquals(52000.0, $slip['earned_basic']);
        $this->assertFloatEquals(0.0, $slip['overtime_pay']);
        $this->assertFloatEquals(520.0, $slip['tax_tds_amount']);
        $this->assertFloatEquals(51480.0, $slip['net_salary']);
    }

    public function testOvertimeAndProratedAttendancePayroll(): void
    {
        // Base = 52,000. Present = 13 days (50% = 26,000)
        // Hourly rate = 2,000 / 8 = 250
        // Overtime 10 hours @ 1.5x = 10 * 375 = 3,750
        // Gross = 26,000 + 3,750 = 29,750
        // Advance deduction = 5,000. TDS 1% = 297.50
        // Total deduction = 5,297.50 -> Net = 24,452.50
        $slip = $this->payroll->computeMonthlyPayroll(52000.0, 13, 10.0, 0.0, 5000.0, 1.0);

        $this->assertFloatEquals(26000.0, $slip['earned_basic']);
        $this->assertFloatEquals(3750.0, $slip['overtime_pay']);
        $this->assertFloatEquals(29750.0, $slip['gross_earnings']);
        $this->assertFloatEquals(297.50, $slip['tax_tds_amount']);
        $this->assertFloatEquals(24452.50, $slip['net_salary']);
    }
}
