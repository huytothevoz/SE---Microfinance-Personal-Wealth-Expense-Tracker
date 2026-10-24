<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $tb = $ctx['reconService']->getTrialBalance(1);

    return [
        'totalDebit' => $tb['totalDebit'],
        'totalCredit' => $tb['totalCredit'],
        'equilibriumDiff' => $tb['equilibriumDiff'],
        'isBalanced' => $tb['isBalanced'],
        'hasAccounts' => count($tb['accounts']) > 0,
        'balancedProof' => ($tb['isBalanced'] === true && $tb['equilibriumDiff'] === '0.00000000'),
    ];
});
