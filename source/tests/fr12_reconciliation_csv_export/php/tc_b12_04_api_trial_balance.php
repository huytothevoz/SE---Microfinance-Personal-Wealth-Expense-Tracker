<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $req = createTestRequest();

    ob_start();
    $ctx['controller']->trialBalance($req);
    $output = ob_get_clean();
    $json = json_decode($output, true);

    $tb = $json['data']['trialBalance'] ?? [];

    return [
        'success' => $json['success'] ?? false,
        'http_status' => http_response_code(),
        'isBalanced' => $tb['isBalanced'] ?? false,
        'totalDebit' => $tb['totalDebit'] ?? '',
        'totalCredit' => $tb['totalCredit'] ?? '',
        'equilibriumDiff' => $tb['equilibriumDiff'] ?? '',
        'hasAccounts' => count($tb['accounts'] ?? []) > 0,
    ];
});
