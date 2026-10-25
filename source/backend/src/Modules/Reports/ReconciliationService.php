<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Reports;

use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Admin\AuditLogDAO;
use WealthFlow\Modules\Wallets\WalletDAO;

final class ReconciliationService
{
    private ReportDAO $reportDao;
    private WalletDAO $walletDao;
    private AuditLogDAO $auditLogDao;

    public function __construct(
        ?ReportDAO $reportDao = null,
        ?WalletDAO $walletDao = null,
        ?AuditLogDAO $auditLogDao = null
    ) {
        $this->reportDao = $reportDao ?? new ReportDAO();
        $this->walletDao = $walletDao ?? new WalletDAO();
        $this->auditLogDao = $auditLogDao ?? new AuditLogDAO();
    }

    /**
     * Reconcile a single wallet's cached balance against its double-entry ledger entries.
     *
     * @return array{
     *     walletId: int,
     *     walletName: string,
     *     currency: string,
     *     cachedBalance: string,
     *     ledgerSum: string,
     *     delta: string,
     *     status: string,
     *     isAudited: bool
     * }
     */
    public function reconcileWallet(int $userId, int $walletId): array
    {
        $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
        if ($wallet === null) {
            throw new ApiException('WALLET_NOT_FOUND', 'Ví không tồn tại hoặc không thuộc quyền sở hữu của bạn.', 404);
        }

        $cachedBalance = number_format((float) $wallet['cached_balance'], 8, '.', '');
        $ledgerSum = $this->reportDao->getWalletLedgerSum($walletId, $userId);
        $delta = bcsub($cachedBalance, $ledgerSum, 8);
        $isMatched = bccomp($delta, '0.00000000', 8) === 0;

        if (!$isMatched) {
            // BR12, BR14: Trigger security alert log when mismatch is detected
            $this->auditLogDao->logSecurityEvent($userId, 'LEDGER_MISMATCH_WALLET_' . $walletId);
        }

        return [
            'walletId' => $walletId,
            'walletName' => (string) $wallet['name'],
            'currency' => (string) $wallet['currency'],
            'cachedBalance' => $cachedBalance,
            'ledgerSum' => $ledgerSum,
            'delta' => $delta,
            'status' => $isMatched ? 'MATCHED' : 'MISMATCH',
            'isAudited' => $isMatched,
        ];
    }

    /**
     * Reconcile all active wallets for a user.
     *
     * @return array{
     *     totalWallets: int,
     *     matchedWallets: int,
     *     mismatchedWallets: int,
     *     postedCount: int,
     *     isAllMatched: bool,
     *     wallets: array<int, array<string, mixed>>
     * }
     */
    public function reconcileAllWallets(int $userId): array
    {
        $summary = $this->reportDao->getReconciliationSummary($userId);
        $matchedCount = 0;
        $mismatchedCount = 0;

        foreach ($summary as $item) {
            if ($item['status'] === 'MATCHED') {
                $matchedCount++;
            } else {
                $mismatchedCount++;
                $this->auditLogDao->logSecurityEvent($userId, 'LEDGER_MISMATCH_WALLET_' . $item['walletId']);
            }
        }

        $postedCount = $this->reportDao->countPostedTransactions($userId);

        return [
            'totalWallets' => count($summary),
            'matchedWallets' => $matchedCount,
            'mismatchedWallets' => $mismatchedCount,
            'postedCount' => $postedCount,
            'isAllMatched' => ($mismatchedCount === 0),
            'wallets' => $summary,
        ];
    }

    /**
     * Generate Trial Balance to verify double-entry equilibrium across all user accounts.
     * sum(Debit) == sum(Credit)
     *
     * @return array{
     *     asOfDate: string,
     *     totalDebit: string,
     *     totalCredit: string,
     *     equilibriumDiff: string,
     *     isBalanced: bool,
     *     accounts: array<int, array<string, mixed>>
     * }
     */
    public function getTrialBalance(int $userId, ?string $asOfDate = null): array
    {
        $rows = $this->reportDao->getTrialBalanceRows($userId, $asOfDate);
        $totalDebit = '0.00000000';
        $totalCredit = '0.00000000';
        $accounts = [];

        foreach ($rows as $r) {
            $debit = number_format((float) $r['total_debit'], 8, '.', '');
            $credit = number_format((float) $r['total_credit'], 8, '.', '');
            $totalDebit = bcadd($totalDebit, $debit, 8);
            $totalCredit = bcadd($totalCredit, $credit, 8);

            $accounts[] = [
                'accountId' => (int) $r['id'],
                'code' => (string) $r['code'],
                'accountType' => (string) $r['account_type'],
                'walletName' => $r['wallet_name'],
                'totalDebit' => $debit,
                'totalCredit' => $credit,
            ];
        }

        $diff = bcsub($totalDebit, $totalCredit, 8);
        $isBalanced = bccomp($diff, '0.00000000', 8) === 0;

        return [
            'asOfDate' => $asOfDate ?? date('Y-m-d'),
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'equilibriumDiff' => $diff,
            'isBalanced' => $isBalanced,
            'accounts' => $accounts,
        ];
    }
}
