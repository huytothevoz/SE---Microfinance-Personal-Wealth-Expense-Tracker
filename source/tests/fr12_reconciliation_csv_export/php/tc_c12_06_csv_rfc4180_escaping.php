<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $exportService = $ctx['exportService'];

    // Field containing comma
    $withComma = $exportService->sanitizeCsvField("Cà phê, trà đá");

    // Field containing double quote
    $withQuote = $exportService->sanitizeCsvField('Bánh "Oreo"');

    // Field containing newline
    $withNewline = $exportService->sanitizeCsvField("Dòng 1\nDòng 2");

    return [
        'comma' => $withComma,
        'quote' => $withQuote,
        'newline' => $withNewline,
        'isCommaQuoted' => ($withComma === '"Cà phê, trà đá"'),
        'isQuoteEscaped' => ($withQuote === '"Bánh ""Oreo"""'),
        'isNewlineQuoted' => str_contains($withNewline, "\n") && str_starts_with($withNewline, '"'),
    ];
});
