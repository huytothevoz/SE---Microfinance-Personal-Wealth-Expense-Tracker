<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use PDO;
use WealthFlow\Config\Database;

final class WalletRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE user_id = ? AND status = "ACTIVE" ORDER BY id ASC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function updateBalance(int $walletId, string|float $newBalance): void
    {
        $stmt = $this->pdo->prepare('UPDATE wallets SET cached_balance = ? WHERE id = ?');
        $stmt->execute([(float) $newBalance, $walletId]);
    }

    public function getOrCreateWalletLedgerAccountId(int $userId, int $walletId): int
    {
        $code = 'ASSET_WALLET_' . $walletId;
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $stmt = $this->pdo->prepare('INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, ?, ?, "ASSET")');
        $stmt->execute([$userId, $walletId, $code]);
        return (int) $this->pdo->lastInsertId();
    }
}
