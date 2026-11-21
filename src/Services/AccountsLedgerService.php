<?php
declare(strict_types=1);

namespace IMS\Services;

use IMS\Core\Database;
use IMS\Security\AuditLogger;
use PDO;

/**
 * Enterprise Double-Entry Accounting Ledger & Credit Aging Engine
 * Enforces balanced Debits = Credits journal entries and calculates aging buckets
 * for Accounts Receivable (AR) and Accounts Payable (AP).
 */
class AccountsLedgerService
{
    public const ACCOUNT_ASSET_CASH = '1010_Cash';
    public const ACCOUNT_ASSET_BANK = '1020_Bank';
    public const ACCOUNT_ASSET_INVENTORY = '1030_InventoryAsset';
    public const ACCOUNT_ASSET_AR = '1040_AccountsReceivable';
    public const ACCOUNT_LIABILITY_AP = '2010_AccountsPayable';
    public const ACCOUNT_REVENUE_SALES = '4010_SalesRevenue';
    public const ACCOUNT_EXPENSE_COGS = '5010_CostOfGoodsSold';
    public const ACCOUNT_EXPENSE_OPERATIONAL = '5020_OperationalExpenses';

    /**
     * Record a balanced double-entry transaction:
     * sum(Debits) MUST EQUAL sum(Credits).
     */
    public function recordJournalEntry(string $referenceType, int $referenceId, array $entries, string $memo = ''): array
    {
        $totalDebits = 0.0;
        $totalCredits = 0.0;

        foreach ($entries as $e) {
            $amount = (float) ($e['amount'] ?? 0.0);
            if ($amount <= 0) {
                return ['success' => false, 'error' => 'All ledger amounts must be strictly positive.'];
            }
            if ($e['type'] === 'debit') {
                $totalDebits += $amount;
            } elseif ($e['type'] === 'credit') {
                $totalCredits += $amount;
            } else {
                return ['success' => false, 'error' => "Invalid entry type '{$e['type']}'. Must be 'debit' or 'credit'."];
            }
        }

        // Float precision comparison check (tolerance 0.001)
        if (abs($totalDebits - $totalCredits) > 0.001) {
            return [
                'success' => false,
                'error' => "Double-entry imbalance: Total Debits ({$totalDebits}) does not match Total Credits ({$totalCredits})."
            ];
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO accounts_ledger 
            (account_type, entry_type, amount, reference_type, reference_id, description, created_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'))");

        foreach ($entries as $e) {
            $stmt->execute([
                $e['account'],
                $e['type'],
                (float) $e['amount'],
                $referenceType,
                $referenceId,
                $memo
            ]);
        }

        AuditLogger::log('LEDGER_POSTED', 'ledger', (string) $referenceId, [
            'reference_type' => $referenceType,
            'total_balanced_amount' => $totalDebits,
            'entry_count' => count($entries)
        ]);

        return [
            'success' => true,
            'balanced_amount' => round($totalDebits, 2),
            'entries_recorded' => count($entries)
        ];
    }

    /**
     * Disaggregate outstanding credit invoices into standard aging buckets:
     * - Current: 0 to 30 days
     * - Past Due 30: 31 to 60 days
     * - Past Due 60: 61 to 90 days
     * - Delinquent: Over 90 days
     */
    public function computeAgingBuckets(array $unpaidInvoices, ?string $referenceDate = null): array
    {
        $refTime = $referenceDate ? strtotime($referenceDate) : time();

        $buckets = [
            'current_0_30' => 0.0,
            'overdue_31_60' => 0.0,
            'overdue_61_90' => 0.0,
            'delinquent_90_plus' => 0.0,
            'total_outstanding' => 0.0
        ];

        foreach ($unpaidInvoices as $inv) {
            $amount = (float) ($inv['balance'] ?? ($inv['total_amount'] ?? 0.0));
            $invDate = strtotime($inv['date'] ?? ($inv['created_at'] ?? 'now'));
            $daysPast = (int) max(0, floor(($refTime - $invDate) / 86400));

            $buckets['total_outstanding'] += $amount;

            if ($daysPast <= 30) {
                $buckets['current_0_30'] += $amount;
            } elseif ($daysPast <= 60) {
                $buckets['overdue_31_60'] += $amount;
            } elseif ($daysPast <= 90) {
                $buckets['overdue_61_90'] += $amount;
            } else {
                $buckets['delinquent_90_plus'] += $amount;
            }
        }

        // Round all amounts
        foreach ($buckets as $k => $v) {
            $buckets[$k] = round($v, 2);
        }

        return $buckets;
    }
}
