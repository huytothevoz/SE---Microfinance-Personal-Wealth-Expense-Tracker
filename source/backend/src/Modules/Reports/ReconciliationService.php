<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Reports;

use InvalidArgumentException;

final class ReconciliationService
{
    private ReportDAO $reportDao;

    public function __construct(?ReportDAO $reportDao = null)
    {
        $this->reportDao = $reportDao ?? new ReportDAO();
    }

    /**
     * Reconcile a single wallet of a user against its double-entry ledger history (FR12.02).
     *
     * @return array<string, mixed>
     */
    public function reconcileWallet(int $userId, int $walletId, ?string $ipAddress = null): array
    {
        $wallets = $this->reportDao->findWalletsForReconciliation($userId, $walletId, true);
        if ($wallets === []) {
            throw new InvalidArgumentException('Ví không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        return $this->evaluateWalletReconciliation($userId, $wallets[0], $ipAddress);
    }

    /**
     * Reconcile all wallets (or filtered by walletId) for a user (FR12.02, UC11, SQ07).
     *
     * @return array<string, mixed>
     */
    public function reconcileAllWallets(
        int $userId,
        ?int $walletId = null,
        bool $includeArchived = true,
        ?string $ipAddress = null
    ): array {
        $wallets = $this->reportDao->findWalletsForReconciliation($userId, $walletId, $includeArchived);
        if ($walletId !== null && $wallets === []) {
            throw new InvalidArgumentException('Ví không tồn tại hoặc không thuộc quyền sở hữu.');
        }

        $items = [];
        $matchedCount = 0;
        $mismatchCount = 0;

        foreach ($wallets as $wallet) {
            $item = $this->evaluateWalletReconciliation($userId, $wallet, $ipAddress);
            $items[] = $item;
            if ($item['isMatched']) {
                $matchedCount++;
            } else {
                $mismatchCount++;
            }
        }

        $trialBalance = $this->getTrialBalance($userId);

        return [
            'overallStatus' => ($mismatchCount === 0 && $trialBalance['isBalanced']) ? 'MATCHED' : 'MISMATCHED',
            'isAllMatched' => $mismatchCount === 0 && $trialBalance['isBalanced'],
            'totalWallets' => count($items),
            'matchedCount' => $matchedCount,
            'mismatchCount' => $mismatchCount,
            'checkedAt' => date('Y-m-d H:i:s'),
            'wallets' => $items,
            'trialBalance' => $trialBalance,
            'recentExports' => $this->reportDao->listExportAuditLogs($userId, 5),
        ];
    }

    /**
     * Compute Trial Balance across all ledger accounts for POSTED & REVERSED transactions (FR12.02, BR01).
     *
     * @return array<string, mixed>
     */
    public function getTrialBalance(int $userId, ?string $asOfDate = null): array
    {
        $rows = $this->reportDao->getTrialBalanceRows($userId, $asOfDate);
        $totalDebitBase = '0.00000000';
        $totalCreditBase = '0.00000000';
        $accounts = [];

        foreach ($rows as $row) {
            $debit = number_format((float) ($row['total_debit_base'] ?? 0), 8, '.', '');
            $credit = number_format((float) ($row['total_credit_base'] ?? 0), 8, '.', '');
            $accountType = strtoupper((string) ($row['account_type'] ?? 'ASSET'));

            // Normal balance side: ASSET & EXPENSE are Debit-normal; LIABILITY, EQUITY, INCOME are Credit-normal
            $netBase = in_array($accountType, ['ASSET', 'EXPENSE'], true)
                ? bcsub($debit, $credit, 8)
                : bcsub($credit, $debit, 8);

            $totalDebitBase = bcadd($totalDebitBase, $debit, 8);
            $totalCreditBase = bcadd($totalCreditBase, $credit, 8);

            $accounts[] = [
                'accountId' => (int) $row['account_id'],
                'accountCode' => (string) $row['account_code'],
                'accountType' => $accountType,
                'walletId' => $row['wallet_id'] !== null ? (int) $row['wallet_id'] : null,
                'walletName' => $row['wallet_name'] !== null ? (string) $row['wallet_name'] : null,
                'entryCount' => (int) ($row['entry_count'] ?? 0),
                'totalDebitBase' => (float) $debit,
                'totalCreditBase' => (float) $credit,
                'netBalanceBase' => (float) $netBase,
            ];
        }

        $differenceBase = bcsub($totalDebitBase, $totalCreditBase, 8);
        $isBalanced = abs((float) $differenceBase) < 0.0001;

        return [
            'asOfDate' => $asOfDate ?? date('Y-m-d'),
            'totalDebitBase' => (float) $totalDebitBase,
            'totalCreditBase' => (float) $totalCreditBase,
            'differenceBase' => (float) $differenceBase,
            'isBalanced' => $isBalanced,
            'status' => $isBalanced ? 'BALANCED' : 'OUT_OF_BALANCE',
            'accounts' => $accounts,
        ];
    }

    /**
     * @param array<string, mixed> $wallet
     * @return array<string, mixed>
     */
    private function evaluateWalletReconciliation(int $userId, array $wallet, ?string $ipAddress = null): array
    {
        $walletId = (int) $wallet['id'];
        $walletType = strtoupper((string) $wallet['wallet_type']);
        $currency = strtoupper((string) $wallet['currency']);
        $cachedBalance = number_format((float) $wallet['cached_balance'], 8, '.', '');

        $entries = $this->reportDao->findWalletLedgerEntries($userId, $walletId);
        $ledgerSum = '0.00000000';
        $totalDebit = '0.00000000';
        $totalCredit = '0.00000000';

        foreach ($entries as $entry) {
            $amt = number_format((float) $entry['amount_original'], 8, '.', '');
            $side = strtoupper((string) $entry['entry_side']);
            $txType = strtoupper((string) ($entry['transaction_type'] ?? ''));
            $desc = (string) ($entry['description'] ?? '');

            if ($side === 'DEBIT') {
                $totalDebit = bcadd($totalDebit, $amt, 8);
                $ledgerSum = bcadd($ledgerSum, $amt, 8);
            } else {
                $totalCredit = bcadd($totalCredit, $amt, 8);
                $isCreditWalletOpening = $walletType === 'CREDIT'
                    && $txType === 'OTHER'
                    && str_starts_with($desc, 'Số dư mở sổ ví:');
                if ($isCreditWalletOpening) {
                    $ledgerSum = bcadd($ledgerSum, $amt, 8);
                } else {
                    $ledgerSum = bcsub($ledgerSum, $amt, 8);
                }
            }
        }

        $delta = bcsub($cachedBalance, $ledgerSum, 8);
        $isMatched = abs((float) $delta) < 0.0001;
        $status = $isMatched ? 'MATCHED' : 'MISMATCHED';

        if (!$isMatched) {
            $this->reportDao->logSecurityEvent(
                $userId,
                'LEDGER_MISMATCH',
                [
                    'walletId' => $walletId,
                    'walletName' => (string) $wallet['name'],
                    'currency' => $currency,
                    'cachedBalance' => (float) $cachedBalance,
                    'ledgerSum' => (float) $ledgerSum,
                    'delta' => (float) $delta,
                ],
                $ipAddress
            );
        }

        return [
            'walletId' => $walletId,
            'walletName' => (string) $wallet['name'],
            'walletType' => $walletType,
            'currency' => $currency,
            'walletStatus' => (string) $wallet['status'],
            'cachedBalance' => (float) $cachedBalance,
            'earmarkedBalance' => (float) ($wallet['earmarked_balance'] ?? 0),
            'ledgerSum' => (float) $ledgerSum,
            'totalDebit' => (float) $totalDebit,
            'totalCredit' => (float) $totalCredit,
            'entryCount' => count($entries),
            'delta' => (float) $delta,
            'discrepancy' => (float) $delta,
            'isMatched' => $isMatched,
            'status' => $status,
            'checkedAt' => date('Y-m-d H:i:s'),
        ];
    }
}
