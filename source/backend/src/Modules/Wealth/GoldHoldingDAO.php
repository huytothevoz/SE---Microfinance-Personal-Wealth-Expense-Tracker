<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wealth;

use PDO;
use WealthFlow\Core\Database;

final class GoldHoldingDAO
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
    public function listByUser(int $userId, bool $includeLiquidated = false): array
    {
        $sql = 'SELECT * FROM gold_assets WHERE user_id = ?';
        if (!$includeLiquidated) {
            $sql .= " AND status = 'HELD'";
        }
        $sql .= ' ORDER BY valuation_date DESC, id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAndUser(int $id, int $userId, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM gold_assets WHERE id = ? AND user_id = ?';
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findHeldByType(int $userId, string $goldType, bool $forUpdate = false): ?array
    {
        $sql = "SELECT * FROM gold_assets WHERE user_id = ? AND gold_type = ? AND status = 'HELD' ORDER BY id ASC LIMIT 1";
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $goldType]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(
        int $userId,
        string $goldType,
        string $quantity,
        string $costBasis,
        string $averageCost,
        string $valuationPerChi,
        string $valuationDate
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO gold_assets
             (user_id, gold_type, quantity_chi, total_cost_basis, weighted_avg_cost,
              valuation_per_chi, valuation_date, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'HELD')"
        );
        $stmt->execute([$userId, $goldType, $quantity, $costBasis, $averageCost, $valuationPerChi, $valuationDate]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateHolding(
        int $id,
        int $userId,
        string $quantity,
        string $costBasis,
        string $averageCost,
        string $valuationPerChi,
        string $valuationDate
    ): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE gold_assets
             SET quantity_chi = ?, total_cost_basis = ?, weighted_avg_cost = ?,
                 valuation_per_chi = ?, valuation_date = ?
             WHERE id = ? AND user_id = ? AND status = 'HELD'"
        );
        $stmt->execute([$quantity, $costBasis, $averageCost, $valuationPerChi, $valuationDate, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function revalue(int $id, int $userId, string $valuationPerChi, string $valuationDate): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE gold_assets SET valuation_per_chi = ?, valuation_date = ?
             WHERE id = ? AND user_id = ? AND status = 'HELD'"
        );
        $stmt->execute([$valuationPerChi, $valuationDate, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function liquidate(int $id, int $userId, string $valuationPerChi, string $valuationDate): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE gold_assets
             SET quantity_chi = 0, valuation_per_chi = ?, valuation_date = ?, status = 'LIQUIDATED'
             WHERE id = ? AND user_id = ? AND status = 'HELD'"
        );
        $stmt->execute([$valuationPerChi, $valuationDate, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function addTransaction(
        int $assetId,
        int $userId,
        string $type,
        string $quantity,
        string $unitPrice,
        string $totalAmount,
        ?int $walletId,
        string $occurredOn
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO gold_transactions
             (gold_asset_id, user_id, transaction_type, quantity_chi, unit_price,
              total_amount, wallet_id, occurred_on)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$assetId, $userId, $type, $quantity, $unitPrice, $totalAmount, $walletId, $occurredOn]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listTransactions(int $assetId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT gt.id, gt.transaction_type, gt.quantity_chi, gt.unit_price,
                    gt.total_amount, gt.wallet_id, gt.occurred_on, w.name AS wallet_name
             FROM gold_transactions gt
             JOIN gold_assets ga ON ga.id = gt.gold_asset_id
             LEFT JOIN wallets w ON w.id = gt.wallet_id
             WHERE gt.gold_asset_id = ? AND ga.user_id = ?
             ORDER BY gt.occurred_on DESC, gt.id DESC'
        );
        $stmt->execute([$assetId, $userId]);
        return $stmt->fetchAll();
    }

    public function getOrCreateLedgerAccountId(int $userId, int $assetId): int
    {
        return $this->getOrCreateAccount($userId, 'ASSET_GOLD_' . $assetId, 'ASSET');
    }

    public function getOrCreateAccount(int $userId, string $code, string $accountType): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
        $stmt->execute([$userId, $code]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES (?, NULL, ?, ?)'
        );
        $stmt->execute([$userId, $code, $accountType]);
        return (int) $this->pdo->lastInsertId();
    }
}
