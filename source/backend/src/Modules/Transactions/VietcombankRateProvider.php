<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

class VietcombankRateProvider
{
    public const SOURCE_URL = 'https://www.vietcombank.com.vn/api/exchangerates';

    /** @return array{provider: string, effectiveAt: string, sourceUrl: string, rates: array<string, string>, buyRates: array<string, string>, sellRates: array<string, string>} */
    public function fetch(): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Máy chủ chưa bật PHP cURL để cập nhật tỷ giá.');
        }

        $url = self::SOURCE_URL . '?date=' . rawurlencode(date('m/d/Y'));
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Không thể khởi tạo kết nối tỷ giá.');
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'WealthFlow/1.0',
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $transportError = curl_error($handle);
        curl_close($handle);

        if (!is_string($body) || $status !== 200) {
            throw new RuntimeException($transportError !== ''
                ? 'Không thể kết nối nguồn tỷ giá Vietcombank.'
                : 'Nguồn tỷ giá Vietcombank trả về trạng thái không hợp lệ.');
        }

        try {
            $payload = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('Nguồn tỷ giá Vietcombank trả về dữ liệu không hợp lệ.');
        }

        $buyRates = [];
        $sellRates = [];
        foreach (($payload['Data'] ?? []) as $row) {
            $currency = strtoupper((string) ($row['currencyCode'] ?? ''));
            $buy = trim((string) ($row['transfer'] ?? ''));
            $sell = trim((string) ($row['sell'] ?? ''));
            if (in_array($currency, ['USD', 'EUR'], true)) {
                if (preg_match('/^\d+(?:\.\d+)?$/D', $buy) && bccomp($buy, '0', 10) === 1) {
                    $buyRates[$currency] = $buy;
                }
                if (preg_match('/^\d+(?:\.\d+)?$/D', $sell) && bccomp($sell, '0', 10) === 1) {
                    $sellRates[$currency] = $sell;
                }
            }
        }
        if (!isset($buyRates['USD'], $buyRates['EUR'], $sellRates['USD'], $sellRates['EUR'])) {
            throw new RuntimeException('Nguồn Vietcombank thiếu tỷ giá mua chuyển khoản hoặc tỷ giá bán USD/EUR.');
        }

        try {
            $effectiveAt = new DateTimeImmutable((string) ($payload['UpdatedDate'] ?? 'now'));
        } catch (\Exception) {
            throw new RuntimeException('Thời điểm cập nhật tỷ giá không hợp lệ.');
        }

        return [
            'provider' => 'VIETCOMBANK_TRANSFER',
            'effectiveAt' => $effectiveAt
                ->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))
                ->format('Y-m-d H:i:s'),
            'sourceUrl' => self::SOURCE_URL,
            'rates' => $buyRates,
            'buyRates' => $buyRates,
            'sellRates' => $sellRates,
        ];
    }
}
