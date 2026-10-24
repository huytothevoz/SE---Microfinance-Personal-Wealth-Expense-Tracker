<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $exportService = $ctx['exportService'];

    $result = $exportService->exportTransactionsCsv(1, [], '127.0.0.1');

    $content = $result['content'];
    $hasBom = str_starts_with($content, "\xEF\xBB\xBF");
    $expectedHash = hash('sha256', $content);
    $hashMatches = ($result['fileHash'] === $expectedHash);

    return [
        'filename' => $result['filename'],
        'rowCount' => $result['rowCount'],
        'fileHash' => $result['fileHash'],
        'hasBom' => $hasBom,
        'hashMatches' => $hashMatches,
        'validExport' => ($hasBom && $hashMatches && $result['rowCount'] > 0),
    ];
});
