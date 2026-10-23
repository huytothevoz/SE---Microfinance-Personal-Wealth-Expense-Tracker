<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wealth;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Wallets\WalletDAO;

final class GoldAssetService
{
    private GoldHoldingDAO $goldDao;
    private WalletDAO $walletDao;
    private LedgerDAO $ledgerDao;

    public function __construct(
        ?GoldHoldingDAO $goldDao = null,
        ?WalletDAO $walletDao = null,
        ?LedgerDAO $ledgerDao = null
    ) {
        $this->goldDao = $goldDao ?? new GoldHoldingDAO();
        $pdo = $this->goldDao->getPdo();
        $this->walletDao = $walletDao ?? new WalletDAO($pdo);
        $this->ledgerDao = $ledgerDao ?? new LedgerDAO($pdo);
    }

    /** @return array<int, array<string, mixed>> */
    public function listAssets(int $userId, bool $includeLiquidated = false): array
    {
        return array_map(fn(array $asset): array => $this->present($asset), $this->goldDao->listByUser($userId, $includeLiquidated));
    }

    /** @return array<string, mixed> */
    public function getAsset(int $id, int $userId): array
    {
        $asset = $this->requireAsset($id, $userId);
        $result = $this->present($asset);
        $result['transactions'] = array_map(static fn(array $row): array => [
            'id' => (int) $row['id'],
            'type' => (string) $row['transaction_type'],
            'quantityChi' => (string) $row['quantity_chi'],
            'unitPrice' => (string) $row['unit_price'],
            'totalAmount' => (string) $row['total_amount'],
            'walletId' => $row['wallet_id'] === null ? null : (int) $row['wallet_id'],
            'walletName' => (string) ($row['wallet_name'] ?? 'Tài sản có sẵn'),
            'occurredOn' => (string) $row['occurred_on'],
        ], $this->goldDao->listTransactions($id, $userId));
        return $result;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function buy(int $userId, array $data, ?string $idempotencyKey): array
    {
        $goldType = $this->goldType($data['goldType'] ?? '');
        $quantity = $this->decimal($data['quantityChi'] ?? null, 'Số lượng vàng', 4);
        $unitPrice = $this->decimal($data['pricePerChi'] ?? null, 'Giá mỗi chỉ', 8);
        $occurredOn = $this->date($data['transactionDate'] ?? date('Y-m-d'));
        $walletId = isset($data['walletId']) && (int) $data['walletId'] > 0 ? (int) $data['walletId'] : null;
        $total = bcmul($quantity, $unitPrice, 8);
        $key = $this->idempotencyKey($idempotencyKey);
        $payload = compact('goldType', 'quantity', 'unitPrice', 'occurredOn', 'walletId');

        return $this->atomicIdempotent($userId, $key, $payload, 201, function () use (
            $userId,
            $goldType,
            $quantity,
            $unitPrice,
            $occurredOn,
            $walletId,
            $total
        ): array {
            $wallet = null;
            if ($walletId !== null) {
                $wallet = $this->walletDao->findByIdAndUser($walletId, $userId, true);
                if ($wallet === null || (string) $wallet['status'] !== 'ACTIVE') {
                    throw new ApiException('NOT_FOUND', 'Ví thanh toán không tồn tại hoặc không thuộc quyền sở hữu.', 404);
                }
                if ((string) $wallet['wallet_type'] === 'CREDIT' || strtoupper((string) $wallet['currency']) !== 'VND') {
                    throw new InvalidArgumentException('Mua vàng chỉ hỗ trợ ví tài sản VND đang hoạt động.');
                }
                $available = bcsub((string) $wallet['cached_balance'], (string) ($wallet['earmarked_balance'] ?? '0'), 8);
                if (bccomp($available, $total, 8) < 0) {
                    throw new ApiException('INSUFFICIENT_FUNDS', 'Số dư khả dụng của ví không đủ để mua vàng.', 422);
                }
            }

            $asset = $this->goldDao->findHeldByType($userId, $goldType, true);
            if ($asset === null) {
                $assetId = $this->goldDao->create($userId, $goldType, $quantity, $total, $unitPrice, $unitPrice, $occurredOn);
            } else {
                $assetId = (int) $asset['id'];
                $newQuantity = bcadd((string) $asset['quantity_chi'], $quantity, 4);
                $newCost = bcadd((string) $asset['total_cost_basis'], $total, 8);
                $newAverage = bcdiv($newCost, $newQuantity, 8);
                if (!$this->goldDao->updateHolding($assetId, $userId, $newQuantity, $newCost, $newAverage, $unitPrice, $occurredOn)) {
                    throw new ApiException('GOLD_UPDATE_CONFLICT', 'Tài sản vàng đã thay đổi. Vui lòng thử lại.', 409);
                }
            }

            $transactionId = $this->ledgerDao->createTransaction([
                'userId' => $userId,
                'categoryId' => null,
                'type' => 'GOLD_PURCHASE',
                'status' => 'POSTED',
                'description' => 'Mua ' . $this->label($goldType),
                'currency' => 'VND',
                'amount' => $total,
                'fxRateSnapshot' => '1.0000000000',
                'occurredOn' => $occurredOn,
                'postedAt' => date('Y-m-d H:i:s'),
                'originalTransactionId' => null,
            ]);
            $goldAccountId = $this->goldDao->getOrCreateLedgerAccountId($userId, $assetId);
            $sourceAccountId = $walletId !== null
                ? $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId)
                : $this->goldDao->getOrCreateAccount($userId, 'EQUITY_GOLD_OPENING', 'EQUITY');
            $entry = [
                'transactionId' => $transactionId,
                'amountOriginal' => $total,
                'currency' => 'VND',
                'amountBase' => $total,
                'fxRateSnapshot' => '1.0000000000',
            ];
            $this->ledgerDao->createLedgerEntry($entry + ['ledgerAccountId' => $goldAccountId, 'entrySide' => 'DEBIT']);
            $this->ledgerDao->createLedgerEntry($entry + ['ledgerAccountId' => $sourceAccountId, 'entrySide' => 'CREDIT']);

            if ($wallet !== null && !$this->walletDao->setBalance(
                $walletId,
                $userId,
                bcsub((string) $wallet['cached_balance'], $total, 8)
            )) {
                throw new ApiException('WALLET_UPDATE_FAILED', 'Không thể cập nhật số dư ví.', 409);
            }
            $this->goldDao->addTransaction($assetId, $userId, 'BUY', $quantity, $unitPrice, $total, $walletId, $occurredOn);
            return ['asset' => $this->getAsset($assetId, $userId), 'transactionId' => $transactionId];
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function revalue(int $id, int $userId, array $data, ?string $idempotencyKey): array
    {
        $price = $this->decimal($data['pricePerChi'] ?? null, 'Giá định giá mỗi chỉ', 8, true);
        $valuationDate = $this->date($data['valuationDate'] ?? date('Y-m-d'));
        $key = $this->idempotencyKey($idempotencyKey);
        $payload = compact('id', 'price', 'valuationDate');
        return $this->atomicIdempotent($userId, $key, $payload, 200, function () use ($id, $userId, $price, $valuationDate): array {
            $asset = $this->goldDao->findByIdAndUser($id, $userId, true);
            if ($asset === null || (string) $asset['status'] !== 'HELD') {
                throw new ApiException('NOT_FOUND', 'Tài sản vàng đang sở hữu không tồn tại.', 404);
            }
            if (!$this->goldDao->revalue($id, $userId, $price, $valuationDate)) {
                throw new ApiException('GOLD_UPDATE_CONFLICT', 'Tài sản vàng đã thay đổi. Vui lòng thử lại.', 409);
            }
            $total = bcmul((string) $asset['quantity_chi'], $price, 8);
            $this->goldDao->addTransaction($id, $userId, 'REVALUATION', (string) $asset['quantity_chi'], $price, $total, null, $valuationDate);
            return ['asset' => $this->getAsset($id, $userId)];
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function liquidate(int $id, int $userId, array $data, ?string $idempotencyKey): array
    {
        $walletId = (int) ($data['walletId'] ?? 0);
        $unitPrice = $this->decimal($data['pricePerChi'] ?? null, 'Giá bán mỗi chỉ', 8);
        $occurredOn = $this->date($data['transactionDate'] ?? date('Y-m-d'));
        $key = $this->idempotencyKey($idempotencyKey);
        $payload = compact('id', 'walletId', 'unitPrice', 'occurredOn');
        return $this->atomicIdempotent($userId, $key, $payload, 200, function () use ($id, $userId, $walletId, $unitPrice, $occurredOn): array {
            $asset = $this->goldDao->findByIdAndUser($id, $userId, true);
            if ($asset === null || (string) $asset['status'] !== 'HELD') {
                throw new ApiException('NOT_FOUND', 'Tài sản vàng đang sở hữu không tồn tại.', 404);
            }
            $wallet = $this->walletDao->findByIdAndUser($walletId, $userId, true);
            if ($wallet === null || (string) $wallet['status'] !== 'ACTIVE'
                || (string) $wallet['wallet_type'] === 'CREDIT'
                || strtoupper((string) $wallet['currency']) !== 'VND') {
                throw new InvalidArgumentException('Hãy chọn ví tài sản VND đang hoạt động để nhận tiền bán vàng.');
            }
            $quantity = (string) $asset['quantity_chi'];
            $proceeds = bcmul($quantity, $unitPrice, 8);
            $cost = (string) $asset['total_cost_basis'];
            $transactionId = $this->ledgerDao->createTransaction([
                'userId' => $userId,
                'categoryId' => null,
                'type' => 'GOLD_SALE',
                'status' => 'POSTED',
                'description' => 'Thanh lý ' . $this->label((string) $asset['gold_type']),
                'currency' => 'VND',
                'amount' => $proceeds,
                'fxRateSnapshot' => '1.0000000000',
                'occurredOn' => $occurredOn,
                'postedAt' => date('Y-m-d H:i:s'),
                'originalTransactionId' => null,
            ]);
            $walletAccountId = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId);
            $goldAccountId = $this->goldDao->getOrCreateLedgerAccountId($userId, $id);
            $this->ledgerEntry($transactionId, $walletAccountId, 'DEBIT', $proceeds);
            $this->ledgerEntry($transactionId, $goldAccountId, 'CREDIT', $cost);
            $difference = bcsub($proceeds, $cost, 8);
            if (bccomp($difference, '0', 8) > 0) {
                $gainAccount = $this->goldDao->getOrCreateAccount($userId, 'INCOME_GOLD_GAIN', 'INCOME');
                $this->ledgerEntry($transactionId, $gainAccount, 'CREDIT', $difference);
            } elseif (bccomp($difference, '0', 8) < 0) {
                $lossAccount = $this->goldDao->getOrCreateAccount($userId, 'EXPENSE_GOLD_LOSS', 'EXPENSE');
                $this->ledgerEntry($transactionId, $lossAccount, 'DEBIT', bcmul($difference, '-1', 8));
            }
            if (!$this->walletDao->setBalance($walletId, $userId, bcadd((string) $wallet['cached_balance'], $proceeds, 8))) {
                throw new ApiException('WALLET_UPDATE_FAILED', 'Không thể cập nhật số dư ví.', 409);
            }
            if (!$this->goldDao->liquidate($id, $userId, $unitPrice, $occurredOn)) {
                throw new ApiException('GOLD_UPDATE_CONFLICT', 'Tài sản vàng đã thay đổi. Vui lòng thử lại.', 409);
            }
            $this->goldDao->addTransaction($id, $userId, 'SELL', $quantity, $unitPrice, $proceeds, $walletId, $occurredOn);
            return ['asset' => $this->getAsset($id, $userId), 'transactionId' => $transactionId];
        });
    }

    /** @param callable(): array<string, mixed> $operation @return array<string, mixed> */
    private function atomicIdempotent(int $userId, ?string $key, array $payload, int $code, callable $operation): array
    {
        $pdo = $this->goldDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            if ($key !== null) {
                $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
                $record = $this->ledgerDao->findIdempotency($userId, $key);
                if ($record !== null) {
                    if (!hash_equals((string) $record['request_hash'], $hash)) {
                        throw new ApiException('IDEMPOTENCY_CONFLICT', 'Khóa chống trùng đã được dùng cho yêu cầu khác.', 409);
                    }
                    $response = json_decode((string) $record['response_json'], true, 512, JSON_THROW_ON_ERROR);
                    if ($ownsTransaction) {
                        $pdo->commit();
                    }
                    return is_array($response) ? $response : [];
                }
            }
            $response = $operation();
            if ($key !== null) {
                $transactionId = isset($response['transactionId']) ? (int) $response['transactionId'] : null;
                $this->ledgerDao->saveIdempotency(
                    $userId,
                    $key,
                    hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                    $transactionId,
                    $code,
                    json_encode($response, JSON_THROW_ON_ERROR)
                );
            }
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $response;
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    private function ledgerEntry(int $transactionId, int $accountId, string $side, string $amount): void
    {
        $this->ledgerDao->createLedgerEntry([
            'transactionId' => $transactionId,
            'ledgerAccountId' => $accountId,
            'entrySide' => $side,
            'amountOriginal' => $amount,
            'currency' => 'VND',
            'amountBase' => $amount,
            'fxRateSnapshot' => '1.0000000000',
        ]);
    }

    /** @return array<string, mixed> */
    private function requireAsset(int $id, int $userId): array
    {
        $asset = $this->goldDao->findByIdAndUser($id, $userId);
        if ($asset === null) {
            throw new ApiException('NOT_FOUND', 'Không tìm thấy tài sản vàng này.', 404);
        }
        return $asset;
    }

    private function goldType(mixed $value): string
    {
        $type = strtoupper(trim((string) $value));
        if (!in_array($type, ['RING_9999', 'SJC_BAR', 'GOLD_24K'], true)) {
            throw new InvalidArgumentException('Loại vàng không hợp lệ.');
        }
        return $type;
    }

    private function decimal(mixed $value, string $label, int $scale, bool $allowZero = false): string
    {
        $raw = trim((string) $value);
        if (!preg_match('/^\d+(?:\.\d{1,' . $scale . '})?$/D', $raw)) {
            throw new InvalidArgumentException($label . ' không hợp lệ.');
        }
        $comparison = bccomp($raw, '0', $scale);
        if ($comparison < 0 || (!$allowZero && $comparison === 0)) {
            throw new InvalidArgumentException($label . ($allowZero ? ' không được âm.' : ' phải lớn hơn 0.'));
        }
        return bcadd($raw, '0', $scale);
    }

    private function date(mixed $value): string
    {
        $raw = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if (!$date || $date->format('Y-m-d') !== $raw || $date > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('Ngày giao dịch/định giá không hợp lệ hoặc nằm trong tương lai.');
        }
        return $raw;
    }

    private function idempotencyKey(?string $key): ?string
    {
        if ($key === null || trim($key) === '') {
            throw new ApiException('IDEMPOTENCY_KEY_REQUIRED', 'Yêu cầu ghi tài sản cần Idempotency-Key.', 400);
        }
        $key = strtolower(trim($key));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $key)) {
            throw new ApiException('INVALID_IDEMPOTENCY_KEY', 'Idempotency-Key phải là UUID v4 hợp lệ.', 400);
        }
        return $key;
    }

    /** @param array<string, mixed> $asset @return array<string, mixed> */
    private function present(array $asset): array
    {
        $quantity = (string) $asset['quantity_chi'];
        $valuation = (string) $asset['valuation_per_chi'];
        return [
            'id' => (int) $asset['id'],
            'goldType' => (string) $asset['gold_type'],
            'name' => $this->label((string) $asset['gold_type']),
            'quantityChi' => $quantity,
            'totalCostBasis' => (string) $asset['total_cost_basis'],
            'weightedAverageCost' => (string) $asset['weighted_avg_cost'],
            'valuationPerChi' => $valuation,
            'currentValue' => bcmul($quantity, $valuation, 8),
            'valuationDate' => (string) $asset['valuation_date'],
            'status' => (string) $asset['status'],
            'currency' => 'VND',
        ];
    }

    private function label(string $type): string
    {
        return match ($type) {
            'RING_9999' => 'Vàng nhẫn 9999',
            'SJC_BAR' => 'Vàng miếng SJC',
            'GOLD_24K' => 'Vàng 24K',
            default => 'Tài sản vàng',
        };
    }
}
