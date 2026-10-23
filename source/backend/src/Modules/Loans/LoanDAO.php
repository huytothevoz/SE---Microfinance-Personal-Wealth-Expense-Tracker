<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Loans;

use PDO;
use WealthFlow\Core\Database;

final class LoanDAO
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /** @return array<int, array<string, mixed>> */
    public function listByUser(int $userId, ?string $direction = null, ?string $status = null): array
    {
        $sql = 'SELECT l.*, w.name AS wallet_name
                FROM micro_loans l
                LEFT JOIN wallets w ON w.id = l.wallet_id
                WHERE l.user_id = ?';
        $params = [$userId];
        if ($direction !== null) {
            $sql .= ' AND l.loan_direction = ?';
            $params[] = $direction;
        }
        if ($status !== null) {
            $sql .= ' AND l.status = ?';
            $params[] = $status;
        }
        $sql .= " ORDER BY CASE l.status WHEN 'ACTIVE' THEN 0 WHEN 'DEFAULTED' THEN 1 ELSE 2 END,
                  COALESCE(l.next_due_date, '9999-12-31'), l.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        return $this->findOwned($id, $userId, false);
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUserForUpdate(int $id, int $userId): ?array
    {
        return $this->findOwned($id, $userId, true);
    }

    /** @return array<string, mixed>|null */
    private function findOwned(int $id, int $userId, bool $forUpdate): ?array
    {
        $sql = 'SELECT l.*, w.name AS wallet_name
             FROM micro_loans l
             LEFT JOIN wallets w ON w.id = l.wallet_id
             WHERE l.id = ? AND l.user_id = ?';
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO micro_loans
             (user_id, wallet_id, title, partner_name, loan_direction, currency,
              principal_original, principal_remaining, interest_rate_year, term_months,
              start_date, next_due_date, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "ACTIVE")'
        );
        $stmt->execute([
            $data['userId'], $data['walletId'], $data['title'], $data['partnerName'],
            $data['direction'], $data['currency'], $data['principal'], $data['principal'],
            $data['interestRate'], $data['termMonths'], $data['startDate'], $data['nextDueDate'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function addSchedule(
        int $loanId,
        int $period,
        string $dueDate,
        string $principalDue,
        string $interestDue
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO loan_repayment_schedules
             (loan_id, period_number, due_date, principal_due, interest_due)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$loanId, $period, $dueDate, $principalDue, $interestDue]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listSchedules(int $loanId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.* FROM loan_repayment_schedules s
             JOIN micro_loans l ON l.id = s.loan_id
             WHERE s.loan_id = ? AND l.user_id = ?
             ORDER BY s.period_number ASC'
        );
        $stmt->execute([$loanId, $userId]);
        return $stmt->fetchAll();
    }

    public function addPayment(
        int $loanId,
        ?int $transactionId,
        string $principal,
        string $interest,
        string $paidOn
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO loan_payments
             (loan_id, transaction_id, principal_amount, interest_fee_amount, paid_on)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$loanId, $transactionId, $principal, $interest, $paidOn]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateAfterPayment(int $loanId, int $userId, string $remaining, string $status, ?string $nextDueDate): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE micro_loans SET principal_remaining = ?, status = ?, next_due_date = ?
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$remaining, $status, $nextDueDate, $loanId, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function nextUnpaidDueDate(int $loanId): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT due_date FROM loan_repayment_schedules
             WHERE loan_id = ? AND status <> 'PAID'
             ORDER BY period_number ASC LIMIT 1"
        );
        $stmt->execute([$loanId]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    public function applyPaymentToSchedules(int $loanId, string $principal, string $interest): void
    {
        $remainingPrincipal = $principal;
        $remainingInterest = $interest;
        $stmt = $this->pdo->prepare(
            "SELECT * FROM loan_repayment_schedules
             WHERE loan_id = ? AND status <> 'PAID'
             ORDER BY period_number ASC"
        );
        $stmt->execute([$loanId]);
        foreach ($stmt->fetchAll() as $row) {
            if (bccomp($remainingPrincipal, '0', 8) === 0 && bccomp($remainingInterest, '0', 8) === 0) break;
            $principalOpen = bcsub((string) $row['principal_due'], (string) $row['principal_paid'], 8);
            $interestOpen = bcsub((string) $row['interest_due'], (string) $row['interest_paid'], 8);
            $principalApplied = bccomp($remainingPrincipal, $principalOpen, 8) > 0 ? $principalOpen : $remainingPrincipal;
            $interestApplied = bccomp($remainingInterest, $interestOpen, 8) > 0 ? $interestOpen : $remainingInterest;
            $newPrincipalPaid = bcadd((string) $row['principal_paid'], $principalApplied, 8);
            $newInterestPaid = bcadd((string) $row['interest_paid'], $interestApplied, 8);
            $principalDone = bccomp($newPrincipalPaid, (string) $row['principal_due'], 8) >= 0;
            $interestDone = bccomp($newInterestPaid, (string) $row['interest_due'], 8) >= 0;
            $status = $principalDone && $interestDone ? 'PAID'
                : (bccomp($newPrincipalPaid, '0', 8) > 0 || bccomp($newInterestPaid, '0', 8) > 0 ? 'PARTIAL' : (string) $row['status']);
            $update = $this->pdo->prepare(
                'UPDATE loan_repayment_schedules
                 SET principal_paid = ?, interest_paid = ?, status = ? WHERE id = ?'
            );
            $update->execute([$newPrincipalPaid, $newInterestPaid, $status, $row['id']]);
            $remainingPrincipal = bcsub($remainingPrincipal, $principalApplied, 8);
            $remainingInterest = bcsub($remainingInterest, $interestApplied, 8);
            if (bccomp($remainingPrincipal, '0.00000100', 8) <= 0) $remainingPrincipal = '0.00000000';
            if (bccomp($remainingInterest, '0.00000100', 8) <= 0) $remainingInterest = '0.00000000';
        }
    }

    public function getOrCreateLoanAccount(int $userId, int $loanId, string $direction): int
    {
        $code = ($direction === 'BORROWED' ? 'LIABILITY_LOAN_' : 'ASSET_LOAN_RECEIVABLE_') . $loanId;
        $type = $direction === 'BORROWED' ? 'LIABILITY' : 'ASSET';
        return $this->getOrCreateAccount($userId, $code, $type);
    }

    public function getOrCreateAccount(int $userId, string $code, string $type): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id !== false) return (int) $id;
        $stmt = $this->pdo->prepare(
            'INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, NULL, ?, ?)'
        );
        $stmt->execute([$userId, $code, $type]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listPayments(int $loanId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.* FROM loan_payments p
             JOIN micro_loans l ON l.id = p.loan_id
             WHERE p.loan_id = ? AND l.user_id = ?
             ORDER BY p.paid_on DESC, p.id DESC'
        );
        $stmt->execute([$loanId, $userId]);
        return $stmt->fetchAll();
    }
}
