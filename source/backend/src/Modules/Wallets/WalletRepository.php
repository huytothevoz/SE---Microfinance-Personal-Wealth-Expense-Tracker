<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use PDO;
use RuntimeException;
use WealthFlow\Config\Database;

final class WalletRepository
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
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare($this->walletSelect()
            . " WHERE id = ? AND user_id = ? AND status <> 'DELETED'");
        $stmt->execute([$id, $userId]);
        $wallet = $stmt->fetch();
        return $wallet ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findActiveForUpdate(int $id, int $userId): ?array
    {
        $sql = $this->walletSelect() . " WHERE id = ? AND user_id = ? AND status = 'ACTIVE'";
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $wallet = $stmt->fetch();
        return $wallet ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findArchivedForUpdate(int $id, int $userId): ?array
    {
        $sql = $this->walletSelect() . " WHERE id = ? AND user_id = ? AND status = 'ARCHIVED'";
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $wallet = $stmt->fetch();
        return $wallet ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllByUser(int $userId): array
    {
        return $this->listByUser($userId, 'ACTIVE');
    }

    /** @return array<int, array<string, mixed>> */
    public function listByUser(int $userId, string $status = 'ALL'): array
    {
        $sql = $this->walletSelect() . " WHERE user_id = ? AND status <> 'DELETED'";
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

    public function activeNameExists(int $userId, string $nameKey, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM wallets WHERE user_id = ? AND status = 'ACTIVE' AND active_name_key = ?";
        $params = [$userId, $nameKey];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    /** @param array<string, string|int|null> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO wallets
            (user_id, name, active_name_key, wallet_type, currency, cached_balance, status, icon_key, color_key)
            VALUES (?, ?, ?, ?, ?, ?, "ACTIVE", ?, ?)');
        $stmt->execute([
            $data['userId'],
            $data['name'],
            $data['activeNameKey'],
            $data['type'],
            $data['currency'],
            $data['balance'],
            $data['iconKey'],
            $data['colorKey'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateMetadata(
        int $id,
        int $userId,
        string $name,
        string $nameKey,
        string $iconKey,
        string $colorKey
    ): bool {
        $stmt = $this->pdo->prepare("UPDATE wallets
            SET name = ?, active_name_key = ?, icon_key = ?, color_key = ?
            WHERE id = ? AND user_id = ? AND status = 'ACTIVE'");
        $stmt->execute([$name, $nameKey, $iconKey, $colorKey, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function hasActiveRecurringSchedule(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM recurring_schedules
            WHERE wallet_id = ? AND user_id = ? AND status = 'ACTIVE' LIMIT 1");
        $stmt->execute([$id, $userId]);
        return $stmt->fetchColumn() !== false;
    }

    public function archive(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE wallets
            SET status = 'ARCHIVED', active_name_key = NULL, archived_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ? AND status = 'ACTIVE'");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function markDeleted(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE wallets
            SET status = 'DELETED', active_name_key = NULL, deleted_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ? AND status = 'ARCHIVED'");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function setBalance(int $id, int $userId, string $balance): bool
    {
        $stmt = $this->pdo->prepare("UPDATE wallets SET cached_balance = ?
            WHERE id = ? AND user_id = ? AND status = 'ACTIVE'");
        $stmt->execute([$balance, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function getUserBaseCurrency(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT base_currency FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $currency = $stmt->fetchColumn();
        return $currency === false ? null : strtoupper((string) $currency);
    }

    public function adjustBalance(int $walletId, float $delta, bool $allowNegative = false): float
    {
        $sql = 'UPDATE wallets SET cached_balance = cached_balance + ? WHERE id = ?';
        $params = [$delta, $walletId];
        if ($delta < 0 && !$allowNegative) {
            $sql .= " AND (wallet_type = 'CREDIT' OR cached_balance >= ?)";
            $params[] = abs($delta);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Số dư ví không đủ chi trả khoản chi này.', 422);
        }

        $balance = $this->pdo->prepare('SELECT cached_balance FROM wallets WHERE id = ?');
        $balance->execute([$walletId]);
        $value = $balance->fetchColumn();
        if ($value === false) {
            throw new RuntimeException('Ví không còn tồn tại.');
        }
        return (float) $value;
    }

    public function getOrCreateWalletLedgerAccountId(int $userId, int $walletId): int
    {
        return $this->getOrCreateLedgerAccountId(
            $userId,
            $walletId,
            'ASSET_WALLET_' . $walletId,
            'ASSET'
        );
    }

    public function getOrCreateOpeningEquityAccountId(int $userId): int
    {
        return $this->getOrCreateLedgerAccountId($userId, null, 'EQUITY_OPENING_BALANCE', 'EQUITY');
    }

    public function getOrCreateBalanceAdjustmentAccountId(int $userId): int
    {
        return $this->getOrCreateLedgerAccountId($userId, null, 'EQUITY_BALANCE_ADJUSTMENT', 'EQUITY');
    }

    private function getOrCreateLedgerAccountId(
        int $userId,
        ?int $walletId,
        string $code,
        string $accountType
    ): int {
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare('INSERT INTO ledger_accounts
            (user_id, wallet_id, code, account_type) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $walletId, $code, $accountType]);
        return (int) $this->pdo->lastInsertId();
    }

    private function walletSelect(): string
    {
        return 'SELECT id, user_id, name, wallet_type, currency, cached_balance, status,
            icon_key, color_key, archived_at, deleted_at, created_at, updated_at FROM wallets';
    }
}
