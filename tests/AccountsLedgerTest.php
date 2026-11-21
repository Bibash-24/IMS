<?php
declare(strict_types=1);

namespace IMS\Tests;

use IMS\Services\AccountsLedgerService;

class AccountsLedgerTest extends TestCase
{
    private AccountsLedgerService $ledger;

    public function setUp(): void
    {
        parent::setUp();
        $this->ledger = new AccountsLedgerService();
    }

    public function testRecordBalancedJournalEntry(): void
    {
        // Sale of goods for 50,000 cash
        // Debit: Cash 50,000 | Credit: Sales Revenue 50,000
        $entries = [
            ['account' => AccountsLedgerService::ACCOUNT_ASSET_CASH, 'type' => 'debit', 'amount' => 50000.0],
            ['account' => AccountsLedgerService::ACCOUNT_REVENUE_SALES, 'type' => 'credit', 'amount' => 50000.0]
        ];

        $result = $this->ledger->recordJournalEntry('sale', 101, $entries, 'Retail POS Sale #101');

        $this->assertTrue($result['success']);
        $this->assertFloatEquals(50000.0, $result['balanced_amount']);
        $this->assertEquals(2, $result['entries_recorded']);
    }

    public function testRejectsUnbalancedJournalEntry(): void
    {
        // Unbalanced: Debit 50,000 | Credit 45,000 (Mismatch)
        $entries = [
            ['account' => AccountsLedgerService::ACCOUNT_ASSET_CASH, 'type' => 'debit', 'amount' => 50000.0],
            ['account' => AccountsLedgerService::ACCOUNT_REVENUE_SALES, 'type' => 'credit', 'amount' => 45000.0]
        ];

        $result = $this->ledger->recordJournalEntry('sale', 102, $entries);

        $this->assertFalse($result['success']);
        $this->assertContains('Double-entry imbalance', $result['error']);
    }

    public function testComputeAgingBucketsAccurateBucketing(): void
    {
        $refDate = '2026-11-20';
        $invoices = [
            ['id' => 1, 'balance' => 15000.0, 'date' => '2026-11-10'], // 10 days old -> current 0-30
            ['id' => 2, 'balance' => 25000.0, 'date' => '2026-10-10'], // 41 days old -> overdue 31-60
            ['id' => 3, 'balance' => 40000.0, 'date' => '2026-09-01'], // 80 days old -> overdue 61-90
            ['id' => 4, 'balance' => 10000.0, 'date' => '2026-06-01']  // 172 days old -> delinquent 90+
        ];

        $buckets = $this->ledger->computeAgingBuckets($invoices, $refDate);

        $this->assertFloatEquals(15000.0, $buckets['current_0_30']);
        $this->assertFloatEquals(25000.0, $buckets['overdue_31_60']);
        $this->assertFloatEquals(40000.0, $buckets['overdue_61_90']);
        $this->assertFloatEquals(10000.0, $buckets['delinquent_90_plus']);
        $this->assertFloatEquals(90000.0, $buckets['total_outstanding']);
    }
}
