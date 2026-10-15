<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use PDO;
use WealthFlow\Config\Database;

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

        if ($rate !== false && (float) $rate > 0) {
            return number_format((float) $rate, 8, '.', '');
        }

        // Hardcoded reliable defaults if table has no entry
        return match ("{$from}_{$to}") {
            'USD_VND' => '25400.00000000',
            'EUR_VND' => '27600.00000000',
            'VND_USD' => '0.00003937',
            'VND_EUR' => '0.00003623',
            default => '1.00000000',
        };
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
    public function recordRate(string $fromCurrency, string $toCurrency, float $rate, ?string $provider = 'API'): int
    {
        if ($rate <= 0) {
            throw new \InvalidArgumentException('Tỷ giá hối đoái phải lớn hơn 0.');
        }

        $stmt = $this->pdo->prepare('INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_at, provider)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)');
        $stmt->execute([strtoupper($fromCurrency), strtoupper($toCurrency), $rate, $provider]);
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
}
