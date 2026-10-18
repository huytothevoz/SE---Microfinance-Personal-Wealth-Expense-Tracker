<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use PDO;
use RuntimeException;
use Throwable;
use WealthFlow\Core\Database;

final class ExchangeRateService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getSnapshotRate(string $fromCurrency, string $toCurrency = 'VND'): string
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));
        if ($from === $to) {
            return '1.0000000000';
        }
        $rate = $this->findLatestRate($from, $to);
        if ($rate === null) {
            throw new RuntimeException("Chưa có tỷ giá hợp lệ từ {$from} sang {$to}.", 422);
        }
        return $rate['rate'];
    }

    /** @return array{rate: string, effectiveAt: string, provider: ?string}|null */
    public function findLatestRate(string $fromCurrency, string $toCurrency): ?array
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));
        if ($from === $to) {
            return ['rate' => '1.0000000000', 'effectiveAt' => '', 'provider' => 'IDENTITY'];
        }
        $stmt = $this->pdo->prepare(
            'SELECT rate, effective_at, provider FROM exchange_rates
             WHERE from_currency = ? AND to_currency = ? AND rate > 0
             ORDER BY effective_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute([$from, $to]);
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

    /** @return array{provider: string, effectiveAt: string, sourceUrl: string, rates: array<string, string>} */
    public function syncPublicRates(?VietcombankRateProvider $provider = null): array
    {
        $snapshot = ($provider ?? new VietcombankRateProvider())->fetch();
        $usdVnd = $snapshot['rates']['USD'];
        $eurVnd = $snapshot['rates']['EUR'];
        $pairs = [
            'USD_VND' => $usdVnd,
            'EUR_VND' => $eurVnd,
            'VND_USD' => bcdiv('1', $usdVnd, 10),
            'VND_EUR' => bcdiv('1', $eurVnd, 10),
            'USD_EUR' => bcdiv($usdVnd, $eurVnd, 10),
            'EUR_USD' => bcdiv($eurVnd, $usdVnd, 10),
        ];

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            foreach ($pairs as $pair => $rate) {
                [$from, $to] = explode('_', $pair, 2);
                $this->recordRate($from, $to, $rate, $snapshot['provider'], $snapshot['effectiveAt']);
            }
            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return $snapshot;
    }

    public function convertToBase(string|float $amount, string $rate): string
    {
        $amountValue = is_float($amount) ? number_format($amount, 8, '.', '') : (string) $amount;
        return bcmul($amountValue, $rate, 8);
    }

    public function recordRate(
        string $fromCurrency,
        string $toCurrency,
        string|float $rate,
        ?string $provider = 'API',
        ?string $effectiveAt = null
    ): int {
        $value = trim((string) $rate);
        if (!preg_match('/^\d+(?:\.\d+)?$/D', $value) || bccomp($value, '0', 10) !== 1) {
            throw new \InvalidArgumentException('Tỷ giá hối đoái phải lớn hơn 0.');
        }
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));
        if (!preg_match('/^[A-Z]{3}$/D', $from) || !preg_match('/^[A-Z]{3}$/D', $to) || $from === $to) {
            throw new \InvalidArgumentException('Cặp tiền tệ không hợp lệ.');
        }
        $observedAt = $effectiveAt ?? date('Y-m-d H:i:s');
        $existing = $this->pdo->prepare(
            'SELECT id FROM exchange_rates
             WHERE from_currency = ? AND to_currency = ? AND provider = ? AND effective_at = ? LIMIT 1'
        );
        $existing->execute([$from, $to, $provider, $observedAt]);
        $id = $existing->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_at, provider)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$from, $to, $value, $observedAt, $provider]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function getAllLatestRates(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM exchange_rates ORDER BY effective_at DESC, id DESC LIMIT 20');
        return $stmt->fetchAll();
    }
}
