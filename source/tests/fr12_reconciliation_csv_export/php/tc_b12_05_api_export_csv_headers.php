<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Reports {
    function header(string $header, bool $replace = true, int $response_code = 0): void
    {
        $GLOBALS['__test_headers'][] = $header;
    }
}

namespace {
    require_once __DIR__ . '/base.php';

    runAndSendJson(function () {
        $GLOBALS['__test_headers'] = [];
        $ctx = setupTestContext();
        $req = createTestRequest(['walletId' => '10']);

        ob_start();
        $ctx['controller']->exportCsv($req);
        $output = ob_get_clean();

        $headers = array_merge(headers_list(), $GLOBALS['__test_headers'] ?? []);
        $hasCsvType = false;
        $hasDisposition = false;
        $hasSha256 = false;
        $hasRowCount = false;

        foreach ($headers as $h) {
            $lower = strtolower($h);
            if (str_contains($lower, 'content-type: text/csv')) $hasCsvType = true;
            if (str_contains($lower, 'content-disposition: attachment')) $hasDisposition = true;
            if (str_contains($lower, 'x-content-sha256:')) $hasSha256 = true;
            if (str_contains($lower, 'x-content-row-count:')) $hasRowCount = true;
        }

        $hasBom = str_starts_with($output, "\xEF\xBB\xBF");
        $hasLines = count(explode("\r\n", $output)) > 1;

        return [
            'hasCsvType' => $hasCsvType,
            'hasDisposition' => $hasDisposition,
            'hasSha256' => $hasSha256,
            'hasRowCount' => $hasRowCount,
            'hasBom' => $hasBom,
            'hasLines' => $hasLines,
            'outputLength' => strlen($output),
            'validExportStream' => ($hasCsvType && $hasDisposition && $hasSha256 && $hasRowCount && $hasBom && $hasLines),
        ];
    });
}
