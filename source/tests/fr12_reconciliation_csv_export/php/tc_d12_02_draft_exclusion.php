<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $pdo = $ctx['pdo'];
    $reportDao = $ctx['reportDao'];

    $sumBefore = $reportDao->getWalletLedgerSum(10, 1);

    // Add a draft ledger entry for Wallet 10
    $pdo->exec("INSERT INTO financial_transactions (id, user_id, category_id, transaction_type, status, description, currency, amount, fx_rate_snapshot, occurred_on)
        VALUES (100, 1, 1, 'EXPENSE', 'DRAFT', 'Chi tiêu nháp', 'VND', 5000000, 1, '2026-10-22')");
    $pdo->exec("INSERT INTO ledger_entries (id, transaction_id, ledger_account_id, entry_side, amount_original, currency, amount_base, fx_rate_snapshot) VALUES
        (100, 100, 101, 'CREDIT', 5000000, 'VND', 5000000, 1)");

    $sumAfter = $reportDao->getWalletLedgerSum(10, 1);

    return [
        'sumBefore' => $sumBefore,
        'sumAfter' => $sumAfter,
        'draftIgnored' => ($sumBefore === $sumAfter && $sumAfter === '15000000.00000000'),
    ];
});
