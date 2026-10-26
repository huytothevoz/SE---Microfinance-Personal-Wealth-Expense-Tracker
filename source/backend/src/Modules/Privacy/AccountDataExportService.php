<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Privacy;

use PDO;
use WealthFlow\Modules\Admin\AuditLogDAO;

final class AccountDataExportService
{
    private AuditLogDAO $auditLogDao;

    public function __construct(private readonly PDO $db, ?AuditLogDAO $auditLogDao = null)
    {
        $this->auditLogDao = $auditLogDao ?? new AuditLogDAO($db);
    }

    /**
     * @return array{filename: string, content: string, rowCount: int}
     */
    public function export(int $userId, ?string $clientIp = null): array
    {
        $tables = [
            'wallets' => ['SELECT * FROM wallets WHERE user_id = ? ORDER BY id', [$userId]],
            'categories' => ['SELECT * FROM categories WHERE user_id = ? ORDER BY id', [$userId]],
            'ledgerAccounts' => ['SELECT * FROM ledger_accounts WHERE user_id = ? ORDER BY id', [$userId]],
            'transactions' => ['SELECT * FROM financial_transactions WHERE user_id = ? ORDER BY id', [$userId]],
            'ledgerEntries' => [
                'SELECT e.* FROM ledger_entries e
                 JOIN financial_transactions t ON t.id = e.transaction_id AND t.user_id = ?
                 JOIN ledger_accounts a ON a.id = e.ledger_account_id AND a.user_id = ?
                 ORDER BY e.id',
                [$userId, $userId],
            ],
            'budgets' => ['SELECT * FROM budgets WHERE user_id = ? ORDER BY id', [$userId]],
            'recurringSchedules' => ['SELECT * FROM recurring_schedules WHERE user_id = ? ORDER BY id', [$userId]],
            'recurringExecutions' => [
                'SELECT e.* FROM recurring_executions e
                 JOIN recurring_schedules s ON s.id = e.recurring_schedule_id AND s.user_id = ?
                 ORDER BY e.id',
                [$userId],
            ],
            'loans' => ['SELECT * FROM micro_loans WHERE user_id = ? ORDER BY id', [$userId]],
            'loanRepaymentSchedules' => [
                'SELECT r.* FROM loan_repayment_schedules r
                 JOIN micro_loans l ON l.id = r.loan_id AND l.user_id = ?
                 ORDER BY r.id',
                [$userId],
            ],
            'loanPayments' => [
                'SELECT p.* FROM loan_payments p
                 JOIN micro_loans l ON l.id = p.loan_id AND l.user_id = ?
                 ORDER BY p.id',
                [$userId],
            ],
            'goldAssets' => ['SELECT * FROM gold_assets WHERE user_id = ? ORDER BY id', [$userId]],
            'goldTransactions' => [
                'SELECT t.* FROM gold_transactions t
                 JOIN gold_assets a ON a.id = t.gold_asset_id AND a.user_id = ?
                 WHERE t.user_id = ?
                 ORDER BY t.id',
                [$userId, $userId],
            ],
            'goals' => ['SELECT * FROM financial_goals WHERE user_id = ? ORDER BY id', [$userId]],
            'goalAllocations' => [
                'SELECT a.* FROM goal_allocations a
                 JOIN financial_goals g ON g.id = a.goal_id AND g.user_id = ?
                 ORDER BY a.id',
                [$userId],
            ],
            'netWorthSnapshots' => ['SELECT * FROM net_worth_snapshots WHERE user_id = ? ORDER BY id', [$userId]],
            'notifications' => ['SELECT * FROM notifications WHERE user_id = ? ORDER BY id', [$userId]],
            'securityAuditLogs' => [
                'SELECT id, event, created_at FROM security_audit_logs WHERE user_id = ? ORDER BY id',
                [$userId],
            ],
            'previousExports' => [
                'SELECT id, export_type, row_count, created_at FROM export_audit_logs WHERE user_id = ? ORDER BY id',
                [$userId],
            ],
        ];

        $profileStatement = $this->db->prepare(
            'SELECT id, full_name, email, role, status, base_currency, created_at, updated_at
             FROM users WHERE id = ?'
        );
        $profileStatement->execute([$userId]);
        $profile = $profileStatement->fetch();
        if (!$profile) {
            throw new \RuntimeException('Cannot export account data for a missing user.');
        }

        $data = [];
        $rowCount = 1;
        foreach ($tables as $name => [$sql, $parameters]) {
            $statement = $this->db->prepare($sql);
            $statement->execute($parameters);
            $data[$name] = $statement->fetchAll();
            $rowCount += count($data[$name]);
        }

        $payload = [
            'format' => 'wealthflow-account-export',
            'version' => 1,
            'generatedAt' => gmdate('c'),
            'account' => $profile,
            'data' => $data,
        ];
        $content = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $content .= "\n";
        $this->auditLogDao->logExportAudit($userId, 'ACCOUNT_JSON', $rowCount, hash('sha256', $content), $clientIp);

        return [
            'filename' => 'wealthflow_account_' . gmdate('Ymd_His') . '.json',
            'content' => $content,
            'rowCount' => $rowCount,
        ];
    }
}
