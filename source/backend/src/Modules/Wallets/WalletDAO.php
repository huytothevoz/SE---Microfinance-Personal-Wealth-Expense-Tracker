<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use PDO;
use WealthFlow\Core\Database;

final class WalletDAO
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

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId, bool $forUpdate = false): ?array
    {
        $sql = "SELECT * FROM wallets WHERE id = ? AND user_id = ? AND status IN ('ACTIVE', 'ARCHIVED')";
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $wallet = $stmt->fetch();
        return $wallet ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findActiveForUpdate(int $id, int $userId): ?array
    {
        $sql = "SELECT * FROM wallets WHERE id = ? AND user_id = ? AND status = 'ACTIVE'";
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $wallet = $stmt->fetch();
        return $wallet ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listByUser(int $userId, string $status = 'ALL'): array
    {
        $sql = "SELECT * FROM wallets WHERE user_id = ? AND status IN ('ACTIVE', 'ARCHIVED')";
        $params = [$userId];
        if ($status !== 'ALL') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        $sql .= " ORDER BY CASE status WHEN 'ACTIVE' THEN 0 ELSE 1 END, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function nameExists(int $userId, string $name, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM wallets
                WHERE user_id = ? AND LOWER(name) = LOWER(?) AND status IN ('ACTIVE', 'ARCHIVED')";
        $params = [$userId, $name];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    public function create(int $userId, string $name, string $type, string $currency, string $balance): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO wallets (user_id, name, wallet_type, currency, cached_balance, status)
             VALUES (?, ?, ?, ?, ?, "ACTIVE")'
        );
        $stmt->execute([$userId, $name, $type, $currency, $balance]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateName(int $id, int $userId, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE wallets SET name = ? WHERE id = ? AND user_id = ? AND status = 'ACTIVE'"
        );
        $stmt->execute([$name, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function setBalance(int $id, int $userId, string $balance): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE wallets SET cached_balance = ? WHERE id = ? AND user_id = ? AND status = 'ACTIVE'"
        );
        $stmt->execute([$balance, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function archive(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE wallets SET status = 'ARCHIVED' WHERE id = ? AND user_id = ? AND status = 'ACTIVE'"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function hasActiveRecurringSchedule(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM recurring_schedules
             WHERE wallet_id = ? AND user_id = ? AND status = 'ACTIVE' LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->fetchColumn() !== false;
    }

    public function getUserBaseCurrency(int $userId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT base_currency FROM users WHERE id = ? AND status = 'ACTIVE'");
        $stmt->execute([$userId]);
        $currency = $stmt->fetchColumn();
        return $currency === false ? null : strtoupper((string) $currency);
    }

    public function adjustBalance(int $walletId, string|float|int $delta, bool $allowNegative = false): string
    {
        $deltaValue = is_string($delta) ? $delta : number_format((float) $delta, 8, '.', '');
        $isNegative = bccomp($deltaValue, '0', 8) < 0;
        $sql = 'UPDATE wallets SET cached_balance = cached_balance + ? WHERE id = ?';
        $params = [$deltaValue, $walletId];
        if ($isNegative && !$allowNegative) {
            $sql .= ' AND cached_balance >= ?';
            $params[] = bcmul($deltaValue, '-1', 8);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException('Số dư ví không đủ chi trả khoản này.', 422);
        }
        $balance = $this->pdo->prepare('SELECT cached_balance FROM wallets WHERE id = ?');
        $balance->execute([$walletId]);
        $value = $balance->fetchColumn();
        if ($value === false) {
            throw new \RuntimeException('Ví không còn tồn tại.', 404);
        }
        return (string) $value;
    }

    public function getOrCreateWalletLedgerAccountId(int $userId, int $walletId): int
    {
        return $this->getOrCreateLedgerAccountId($userId, 'ASSET_WALLET_' . $walletId, 'ASSET', $walletId);
    }

    public function getOrCreateOpeningEquityAccountId(int $userId): int
    {
        return $this->getOrCreateLedgerAccountId($userId, 'EQUITY_OPENING_BALANCE', 'EQUITY');
    }

    public function getOrCreateBalanceAdjustmentAccountId(int $userId): int
    {
        return $this->getOrCreateLedgerAccountId($userId, 'EQUITY_BALANCE_ADJUSTMENT', 'EQUITY');
    }

    private function getOrCreateLedgerAccountId(
        int $userId,
        string $code,
        string $type,
        ?int $walletId = null
    ): int {
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $walletId, $code, $type]);
        return (int) $this->pdo->lastInsertId();
    }
}
