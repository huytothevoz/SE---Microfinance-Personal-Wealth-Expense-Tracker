<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use WealthFlow\Core\Database;

final class ExchangeRateService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Get the latest effective exchange rate from a currency to the base currency (VND).
     * BR04: Snapshot rate at commit time.
     */
    public function getSnapshotRate(string $fromCurrency, string $toCurrency = 'VND'): string
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));

        if ($from === $to) {
            return '1.00000000';
        }

        $stmt = $this->pdo->prepare('SELECT rate FROM exchange_rates
            WHERE from_currency = ? AND to_currency = ?
            ORDER BY effective_at DESC, id DESC LIMIT 1');
        $stmt->execute([$from, $to]);
        $rate = $stmt->fetchColumn();

        if ($rate !== false && bccomp((string) $rate, '0', 10) === 1) {
            return bcadd((string) $rate, '0', 10);
        }
        throw new RuntimeException("Chưa có tỷ giá hợp lệ từ {$from} sang {$to}.", 422);
    }

    /** @return array{rate: string, effectiveAt: string, provider: ?string}|null */
    public function findLatestRate(string $fromCurrency, string $toCurrency): ?array
    {
        $stmt = $this->pdo->prepare('SELECT rate, effective_at, provider FROM exchange_rates
            WHERE from_currency = ? AND to_currency = ? AND rate > 0
            ORDER BY effective_at DESC, id DESC LIMIT 1');
        $stmt->execute([strtoupper(trim($fromCurrency)), strtoupper(trim($toCurrency))]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return [
            'rate' => (string) $row['rate'],
            'effectiveAt' => (string) $row['effective_at'],
            'provider' => $row['provider'] === null ? null : (string) $row['provider'],
        ];
    }

    /** @return array{provider: string, effectiveAt: string, fetchedAt: string, sourceUrl: string, rates: array<string, string>} */
    public function syncPublicRates(?VietcombankRateProvider $provider = null): array
    {
        $snapshot = ($provider ?? new VietcombankRateProvider())->fetch();
        $snapshot['fetchedAt'] = (new DateTimeImmutable(
            'now',
            new DateTimeZone('Asia/Ho_Chi_Minh')
        ))->format('Y-m-d H:i:s');
        $buyRates = $snapshot['buyRates'] ?? $snapshot['rates'];
        $sellRates = $snapshot['sellRates'] ?? $snapshot['rates'];
        $usdVnd = $buyRates['USD'];
        $eurVnd = $buyRates['EUR'];
        $pairs = [
            'USD_VND' => $usdVnd,
            'EUR_VND' => $eurVnd,
            'VND_USD' => bcdiv('1', $sellRates['USD'], 10),
            'VND_EUR' => bcdiv('1', $sellRates['EUR'], 10),
            'USD_EUR' => bcdiv($usdVnd, $sellRates['EUR'], 10),
            'EUR_USD' => bcdiv($eurVnd, $sellRates['USD'], 10),
        ];
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) $this->pdo->beginTransaction();
        try {
            foreach ($pairs as $pair => $rate) {
                [$from, $to] = explode('_', $pair, 2);
                $this->recordRate($from, $to, $rate, $snapshot['provider'], $snapshot['effectiveAt']);
            }
            $this->recordLastSyncAt($snapshot['fetchedAt']);
            if ($ownsTransaction) $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($ownsTransaction && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
        return $snapshot;
    }

    /**
     * Convert an amount in original currency to base currency using exact BCMath.
     * BR03: Arithmetic precision.
     */
    public function convertToBase(string|float $amount, string $rate): string
    {
        $amountStr = is_float($amount) ? number_format($amount, 8, '.', '') : (string) $amount;
        return bcmul($amountStr, $rate, 4);
    }

    /**
     * Add or update an exchange rate entry (IF02 integration).
     */
    public function recordRate(
        string $fromCurrency,
        string $toCurrency,
        string|float $rate,
        ?string $provider = 'API',
        ?string $effectiveAt = null
    ): int
    {
        $rateValue = trim((string) $rate);
        if (!preg_match('/^\d+(?:\.\d+)?$/D', $rateValue) || bccomp($rateValue, '0', 10) !== 1) {
            throw new \InvalidArgumentException('Tỷ giá hối đoái phải lớn hơn 0.');
        }

        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));
        $observedAt = $effectiveAt ?? date('Y-m-d H:i:s');
        $existing = $this->pdo->prepare('SELECT id FROM exchange_rates
            WHERE from_currency = ? AND to_currency = ? AND provider = ? AND effective_at = ? LIMIT 1');
        $existing->execute([$from, $to, $provider, $observedAt]);
        $existingId = $existing->fetchColumn();
        if ($existingId !== false) {
            $update = $this->pdo->prepare('UPDATE exchange_rates SET rate = ? WHERE id = ?');
            $update->execute([$rateValue, (int) $existingId]);
            return (int) $existingId;
        }

        $stmt = $this->pdo->prepare('INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_at, provider)
            VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$from, $to, $rateValue, $observedAt, $provider]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * List current rates for all supported pairs.
     * @return array<int, array<string, mixed>>
     */
    public function getAllLatestRates(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM exchange_rates ORDER BY effective_at DESC LIMIT 20');
        return $stmt->fetchAll();
    }

    public function getLastSyncAt(): ?string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'EXCHANGE_RATES_LAST_SYNC_AT'");
            $stmt->execute();
            $value = $stmt->fetchColumn();
            return $value === false ? null : (string) $value;
        } catch (\PDOException) {
            return null;
        }
    }

    private function recordLastSyncAt(string $fetchedAt): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO system_config (config_key, config_value)
                 VALUES ('EXCHANGE_RATES_LAST_SYNC_AT', ?)
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$fetchedAt]);
        } catch (\PDOException) {
            // The rate rows remain valid if an older database has no system_config table.
        }
    }
}
