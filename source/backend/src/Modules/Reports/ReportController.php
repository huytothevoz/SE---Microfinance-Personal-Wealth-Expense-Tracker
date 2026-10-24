<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Reports;

use InvalidArgumentException;
use Throwable;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class ReportController
{
    private ReconciliationService $reconciliationService;
    private ExportService $exportService;

    public function __construct(
        ?ReconciliationService $reconciliationService = null,
        ?ExportService $exportService = null
    ) {
        $reportDao = new ReportDAO();
        $this->reconciliationService = $reconciliationService ?? new ReconciliationService($reportDao);
        $this->exportService = $exportService ?? new ExportService($reportDao);
    }

    /**
     * GET /api/reports/reconcile or POST /api/reports/reconcile (FR12.02, UC11, SQ07).
     */
    public function reconcile(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $body = $request->getBody();

        $rawWalletId = $request->getQuery('walletId') ?? ($body['walletId'] ?? null);
        $walletId = ($rawWalletId !== null && $rawWalletId !== '' && $rawWalletId !== 'all')
            ? (int) $rawWalletId
            : null;

        $includeArchivedRaw = $request->getQuery('includeArchived') ?? ($body['includeArchived'] ?? '1');
        $includeArchived = !in_array(strtolower((string) $includeArchivedRaw), ['0', 'false', 'no'], true);

        try {
            $result = $this->reconciliationService->reconcileAllWallets(
                $userId,
                $walletId,
                $includeArchived,
                $request->getClientIp()
            );
            JsonResponse::success($result);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        } catch (Throwable $e) {
            JsonResponse::error('RECONCILIATION_ERROR', 'Không thể thực hiện đối soát sổ cái: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/trial-balance (FR12.02).
     */
    public function trialBalance(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $asOfDate = $request->getQuery('asOfDate');

        try {
            $result = $this->reconciliationService->getTrialBalance(
                $userId,
                $asOfDate !== null ? (string) $asOfDate : null
            );
            JsonResponse::success($result);
        } catch (Throwable $e) {
            JsonResponse::error('TRIAL_BALANCE_ERROR', 'Không thể tổng hợp bảng cân đối thử: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/export/csv (FR12.03, FR12.04, SQ07).
     * Streams UTF-8 with BOM CSV file directly, or returns JSON payload when ?format=json is passed.
     */
    public function exportCsv(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $filters = [
            'type' => $request->getQuery('type') ?? $request->getQuery('transactionType'),
            'status' => $request->getQuery('status'),
            'categoryId' => $request->getQuery('categoryId'),
            'walletId' => $request->getQuery('walletId'),
            'startDate' => $request->getQuery('startDate') ?? $request->getQuery('from'),
            'endDate' => $request->getQuery('endDate') ?? $request->getQuery('to'),
            'search' => $request->getQuery('search') ?? $request->getQuery('q'),
            'limit' => $request->getQuery('limit'),
        ];

        try {
            $export = $this->exportService->exportTransactionsCsv(
                $userId,
                $filters,
                $request->getClientIp()
            );

            $format = strtolower((string) ($request->getQuery('format') ?? 'csv'));
            if ($format === 'json') {
                JsonResponse::success($export);
                return;
            }

            http_response_code(200);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
            header('X-Export-Row-Count: ' . $export['rowCount']);
            header('X-Export-Hash-SHA256: ' . $export['fileHash']);
            echo $export['csv'];
        } catch (Throwable $e) {
            JsonResponse::error('CSV_EXPORT_ERROR', 'Không thể xuất tệp CSV: ' . $e->getMessage(), 500);
        }
    }
}
