<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$phpunit = $root . '/vendor/bin/phpunit';

if (!is_file($phpunit)) {
    fwrite(STDERR, "Chưa có PHPUnit. Chạy composer install trước.\n");
    exit(1);
}

$report = tempnam(sys_get_temp_dir(), 'wealthflow-phpunit-');
if ($report === false) {
    fwrite(STDERR, "Không thể tạo tệp báo cáo tạm.\n");
    exit(1);
}

$arguments = array_slice($argv, 1);
$summaryOnly = in_array('--summary-only', $arguments, true);
$arguments = array_values(array_filter(
    $arguments,
    static fn(string $argument): bool => $argument !== '--summary-only'
));
$command = [PHP_BINARY, $phpunit, '--log-junit', $report, ...$arguments];
$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $root);
if (!is_resource($process)) {
    @unlink($report);
    fwrite(STDERR, "Không thể khởi chạy PHPUnit.\n");
    exit(1);
}
$exitCode = proc_close($process);

if (!is_file($report) || filesize($report) === 0) {
    @unlink($report);
    exit($exitCode);
}

$xml = simplexml_load_file($report);
@unlink($report);
if ($xml === false) {
    fwrite(STDERR, "Không thể đọc báo cáo JUnit XML.\n");
    exit($exitCode === 0 ? 1 : $exitCode);
}

$rows = [];
$summaries = [];
foreach ($xml->xpath('//testcase') ?: [] as $case) {
    $class = (string) $case['class'];
    $shortClass = str_contains($class, '\\')
        ? substr($class, (int) strrpos($class, '\\') + 1)
        : $class;
    $name = (string) $case['name'];
    $seconds = (float) $case['time'];
    $status = isset($case->failure) ? 'FAIL'
        : (isset($case->error) ? 'ERROR' : (isset($case->skipped) ? 'SKIP' : 'PASS'));

    $rows[] = [$shortClass, $name, $seconds * 1000, $status];
    if (!isset($summaries[$shortClass])) {
        $summaries[$shortClass] = ['tests' => 0, 'seconds' => 0.0, 'failed' => 0];
    }
    $summaries[$shortClass]['tests']++;
    $summaries[$shortClass]['seconds'] += $seconds;
    if ($status !== 'PASS') {
        $summaries[$shortClass]['failed']++;
    }
}

if (!$summaryOnly) {
    echo "\nBẢNG THỜI GIAN THEO TEST CASE\n";
    printTable(
        ['#', 'Class/Module', 'Test case', 'Thời gian', 'Kết quả'],
        array_map(
            static fn(array $row, int $index): array => [
                (string) ($index + 1),
                $row[0],
                $row[1],
                number_format($row[2], 3) . ' ms',
                $row[3],
            ],
            $rows,
            array_keys($rows)
        )
    );
}

echo "\nBẢNG TỔNG HỢP THEO CLASS/MODULE\n";
$summaryRows = [];
foreach ($summaries as $class => $summary) {
    $summaryRows[] = [
        $class,
        (string) $summary['tests'],
        number_format($summary['seconds'] * 1000, 3) . ' ms',
        number_format(($summary['seconds'] * 1000) / max(1, $summary['tests']), 3) . ' ms',
        $summary['failed'] === 0 ? 'PASS' : "{$summary['failed']} lỗi",
    ];
}
printTable(
    ['Class/Module', 'Số test', 'Tổng thời gian', 'Trung bình/test', 'Kết quả'],
    $summaryRows
);

exit($exitCode);

/** @param list<string> $headers @param list<list<string>> $rows */
function printTable(array $headers, array $rows): void
{
    $widths = array_map('mb_strlen', $headers);
    foreach ($rows as $row) {
        foreach ($row as $column => $value) {
            $widths[$column] = max($widths[$column] ?? 0, mb_strlen($value));
        }
    }

    $separator = '+';
    foreach ($widths as $width) {
        $separator .= str_repeat('-', $width + 2) . '+';
    }

    $render = static function (array $row) use ($widths): string {
        $line = '|';
        foreach ($widths as $column => $width) {
            $value = $row[$column] ?? '';
            $line .= ' ' . $value . str_repeat(' ', $width - mb_strlen($value)) . ' |';
        }
        return $line;
    };

    echo $separator, PHP_EOL;
    echo $render($headers), PHP_EOL;
    echo $separator, PHP_EOL;
    foreach ($rows as $row) {
        echo $render($row), PHP_EOL;
    }
    echo $separator, PHP_EOL;
}
