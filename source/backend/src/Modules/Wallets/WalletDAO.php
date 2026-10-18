<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use PDO;
use RuntimeException;
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
        $sql = 'SELECT * FROM wallets WHERE id = ? AND user_id = ?';
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllByUser(int $userId, bool $includeArchived = false): array
    {
        $sql = 'SELECT * FROM wallets WHERE user_id = ?';
        if (!$includeArchived) {
            $sql .= " AND status = 'ACTIVE'";
        }
        $sql .= ' ORDER BY id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function create(
        int $userId,
        string $name,
        string $walletType,
        string $currency = 'VND',
        string $initialBalance = '0.00000000'
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO wallets (user_id, name, wallet_type, currency, cached_balance, status)
             VALUES (?, ?, ?, ?, ?, "ACTIVE")'
        );
        $stmt->execute([
            $userId,
            trim($name),
            strtoupper($walletType),
            strtoupper($currency),
            $initialBalance,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $userId, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wallets SET name = ? WHERE id = ? AND user_id = ?'
        );
        return $stmt->execute([trim($name), $id, $userId]);
    }

    public function archive(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE wallets SET status = 'ARCHIVED' WHERE id = ? AND user_id = ? AND status = 'ACTIVE' "
        );
        return $stmt->execute([$id, $userId]);
    }

    public function delete(int $id, int $userId): bool
    {
        throw new RuntimeException('Không hỗ trợ xóa cứng ví có liên quan dữ liệu tài chính. Hãy lưu trữ ví.');
    }

    /**
     * Atomically adjust wallet balance with strict BCMath decimal precision (BR01, BR06).
     *
     * @param string|float|int $delta Signed decimal string (e.g. "-150000.00000000" or "500000.00000000")
     * @return string New cached_balance formatted as string
     */
    public function adjustBalance(int $walletId, string|float|int $delta, bool $allowNegative = false): string
    {
        $deltaStr = is_string($delta) ? $delta : number_format((float) $delta, 8, '.', '');
        $isNegative = bccomp($deltaStr, '0', 8) < 0;

        $sql = 'UPDATE wallets SET cached_balance = cached_balance + ? WHERE id = ?';
        $params = [$deltaStr, $walletId];

        if ($isNegative && !$allowNegative) {
            $absDelta = bcmul($deltaStr, '-1', 8);
            $sql .= " AND (wallet_type = 'CREDIT' OR cached_balance >= ?)";
            $params[] = $absDelta;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Số dư ví không đủ chi trả khoản chi này.', 422);
        }

        $balanceStmt = $this->pdo->prepare('SELECT cached_balance FROM wallets WHERE id = ?');
        $balanceStmt->execute([$walletId]);
        $value = $balanceStmt->fetchColumn();

        if ($value === false) {
            throw new RuntimeException('Ví không còn tồn tại.');
        }

        return (string) $value;
    }

    public function getOrCreateWalletLedgerAccountId(int $userId, int $walletId): int
    {
        $wallet = $this->findByIdAndUser($walletId, $userId);
        if (!$wallet) throw new RuntimeException('Ví không tồn tại.');
        $liability = $wallet['wallet_type'] === 'CREDIT';
        $code = ($liability ? 'LIABILITY_WALLET_' : 'ASSET_WALLET_') . $walletId;
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $walletId, $code, $liability ? 'LIABILITY' : 'ASSET']);
        return (int) $this->pdo->lastInsertId();
    }
}
