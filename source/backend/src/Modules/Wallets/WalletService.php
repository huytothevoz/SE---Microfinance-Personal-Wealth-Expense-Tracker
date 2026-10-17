<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WealthFlow\Core\Database;
use WealthFlow\Modules\Transactions\LedgerDAO;

final class WalletService
{
    private WalletDAO $walletDao;
    private ?LedgerDAO $ledgerDao;

    public function __construct(?WalletDAO $walletDao = null, ?LedgerDAO $ledgerDao = null)
    {
        $this->walletDao = $walletDao ?? new WalletDAO();
        $this->ledgerDao = $ledgerDao ?? new LedgerDAO($this->walletDao->getPdo());
    }

    /** @return array<int, array<string, mixed>> */
    public function getWallets(int $userId, bool $includeArchived = false): array
    {
        return $this->walletDao->findAllByUser($userId, $includeArchived);
    }

    /** @return array<string, mixed> */
    public function getWallet(int $walletId, int $userId): array
    {
        $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
        if (!$wallet) {
            throw new InvalidArgumentException('Ví không tồn tại hoặc không thuộc quyền sở hữu.');
        }
        return $wallet;
    }

    /**
     * Create a new wallet and optionally post an opening balance (BR01, BR02).
     *
     * @param array{
     *     name: string,
     *     walletType: string,
     *     currency?: string,
     *     initialBalance?: float|string|int
     * } $dto
     * @return array<string, mixed>
     */
    public function createWallet(int $userId, array $dto): array
    {
        $name = trim((string) ($dto['name'] ?? ''));
        $walletType = strtoupper(trim((string) ($dto['walletType'] ?? '')));
        $currency = strtoupper(trim((string) ($dto['currency'] ?? 'VND')));
        $initialBalance = isset($dto['initialBalance']) ? (string) $dto['initialBalance'] : '0';

        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Tên ví phải từ 1 đến 120 ký tự.');
        }

        $validTypes = ['CASH', 'BANK', 'E_WALLET', 'SAVINGS', 'CREDIT'];
        if (!in_array($walletType, $validTypes, true)) {
            throw new InvalidArgumentException('Loại ví không hợp lệ: ' . implode(', ', $validTypes));
        }

        if (bccomp($initialBalance, '0', 8) < 0) {
            throw new InvalidArgumentException('Số dư mở sổ ban đầu không được âm.');
        }

        $pdo = $this->walletDao->getPdo();
        $ownsTx = !$pdo->inTransaction();
        if ($ownsTx) {
            $pdo->beginTransaction();
        }

        try {
            $walletId = $this->walletDao->create(
                $userId,
                $name,
                $walletType,
                $currency,
                $initialBalance
            );

            // If initial balance > 0, post opening balance ledger entry
            if (bccomp($initialBalance, '0', 8) > 0) {
                $walletAccountId = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId);

                // Create or find Equity Opening Balance account
                $equityCode = 'EQUITY_OPENING_BALANCE';
                $stmt = $pdo->prepare('SELECT id FROM ledger_accounts WHERE user_id = ? AND code = ?');
                $stmt->execute([$userId, $equityCode]);
                $equityAccountId = $stmt->fetchColumn();
                if (!$equityAccountId) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO ledger_accounts (user_id, code, account_type) VALUES (?, ?, "EQUITY")'
                    );
                    $stmt->execute([$userId, $equityCode]);
                    $equityAccountId = (int) $pdo->lastInsertId();
                } else {
                    $equityAccountId = (int) $equityAccountId;
                }

                $txId = $this->ledgerDao->createTransaction([
                    'userId' => $userId,
                    'categoryId' => null,
                    'type' => 'INCOME',
                    'status' => 'POSTED',
                    'description' => 'Số dư mở sổ ví: ' . $name,
                    'currency' => $currency,
                    'amount' => $initialBalance,
                    'fxRateSnapshot' => '1.00000000',
                    'occurredOn' => date('Y-m-d'),
                    'postedAt' => date('Y-m-d H:i:s'),
                    'originalTransactionId' => null,
                ]);

                // DEBIT Asset:Wallet
                $this->ledgerDao->createLedgerEntry([
                    'transactionId' => $txId,
                    'ledgerAccountId' => $walletAccountId,
                    'entrySide' => 'DEBIT',
                    'amountOriginal' => $initialBalance,
                    'currency' => $currency,
                    'amountBase' => $initialBalance,
                    'fxRateSnapshot' => '1.00000000',
                ]);

                // CREDIT Equity:OpeningBalance
                $this->ledgerDao->createLedgerEntry([
                    'transactionId' => $txId,
                    'ledgerAccountId' => $equityAccountId,
                    'entrySide' => 'CREDIT',
                    'amountOriginal' => $initialBalance,
                    'currency' => $currency,
                    'amountBase' => $initialBalance,
                    'fxRateSnapshot' => '1.00000000',
                ]);
            }

            if ($ownsTx) {
                $pdo->commit();
            }

            return $this->getWallet($walletId, $userId);
        } catch (Throwable $e) {
            if ($ownsTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateWallet(int $walletId, int $userId, array $dto): array
    {
        $wallet = $this->getWallet($walletId, $userId);
        $name = trim((string) ($dto['name'] ?? $wallet['name']));
        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Tên ví phải từ 1 đến 120 ký tự.');
        }

        $this->walletDao->update($walletId, $userId, $name);
        return $this->getWallet($walletId, $userId);
    }

    public function archiveWallet(int $walletId, int $userId): bool
    {
        $this->getWallet($walletId, $userId);
        return $this->walletDao->archive($walletId, $userId);
    }

    public function deleteWallet(int $walletId, int $userId): bool
    {
        $this->getWallet($walletId, $userId);
        return $this->walletDao->delete($walletId, $userId);
    }
}
