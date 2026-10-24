<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $exportService = $ctx['exportService'];

    $sanitizedEqual = $exportService->sanitizeCsvField("=cmd|' /C calc'!A0");
    $sanitizedPlus = $exportService->sanitizeCsvField("+123456");
    $sanitizedMinus = $exportService->sanitizeCsvField("-999999");
    $sanitizedAt = $exportService->sanitizeCsvField("@SUM(1,2)");
    $sanitizedTab = $exportService->sanitizeCsvField("\t=1+1");
    $sanitizedNormal = $exportService->sanitizeCsvField("Mua sắm siêu thị");

    return [
        'equal' => $sanitizedEqual,
        'plus' => $sanitizedPlus,
        'minus' => $sanitizedMinus,
        'at' => $sanitizedAt,
        'tab' => $sanitizedTab,
        'normal' => $sanitizedNormal,
        'allSanitized' => (
            str_starts_with($sanitizedEqual, "'=")
            && str_starts_with($sanitizedPlus, "'+")
            && str_starts_with($sanitizedMinus, "'-")
            && str_contains($sanitizedAt, "'@SUM(1,2)")
            && str_contains($sanitizedTab, "'\t=1+1")
            && $sanitizedNormal === "Mua sắm siêu thị"
        ),
    ];
});
