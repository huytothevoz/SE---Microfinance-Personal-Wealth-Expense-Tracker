<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Reports;

use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class ReportController
{
    private ReconciliationService $reconService;
    private ExportService $exportService;

    public function __construct(
        ?ReconciliationService $reconService = null,
        ?ExportService $exportService = null
    ) {
        $this->reconService = $reconService ?? new ReconciliationService();
        $this->exportService = $exportService ?? new ExportService();
    }

    /**
     * GET /api/reports/reconcile?walletId={optional}
     * If walletId is provided, reconciles single wallet. Otherwise reconciles all wallets.
     */
    public function reconcile(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $walletIdParam = $request->getQuery('walletId');

        try {
            if ($walletIdParam !== null && trim((string) $walletIdParam) !== '') {
                $walletId = (int) $walletIdParam;
                $reconciliation = $this->reconService->reconcileWallet($userId, $walletId);
                JsonResponse::success(['reconciliation' => $reconciliation]);
                return;
            }

            $summary = $this->reconService->reconcileAllWallets($userId);
            JsonResponse::success($summary);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('RECONCILIATION_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/trial-balance?asOfDate={optional}
     * Global chart of accounts equilibrium verification (sum(Debit) == sum(Credit)).
     */
    public function trialBalance(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $asOfDate = $request->getQuery('asOfDate');

        try {
            $tb = $this->reconService->getTrialBalance(
                $userId,
                $asOfDate !== null ? (string) $asOfDate : null
            );
            JsonResponse::success(['trialBalance' => $tb]);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('TRIAL_BALANCE_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/export/csv?from={date}&to={date}&walletId={id}&type={type}
     * Streams safe CSV with UTF-8 BOM, sanitized fields against Formula Injection, and SHA-256 audit.
     */
    public function exportCsv(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));

        $filters = [];
        $walletId = $request->getQuery('walletId');
        if ($walletId !== null && trim((string) $walletId) !== '') {
            $filters['walletId'] = (int) $walletId;
        }

        $from = $request->getQuery('from') ?? $request->getQuery('startDate');
        if ($from !== null && trim((string) $from) !== '') {
            $filters['startDate'] = trim((string) $from);
        }

        $to = $request->getQuery('to') ?? $request->getQuery('endDate');
        if ($to !== null && trim((string) $to) !== '') {
            $filters['endDate'] = trim((string) $to);
        }

        $type = $request->getQuery('type');
        if ($type !== null && trim((string) $type) !== '') {
            $filters['type'] = trim((string) $type);
        }

        $status = $request->getQuery('status');
        if ($status !== null && trim((string) $status) !== '') {
            $filters['status'] = trim((string) $status);
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            $res = $this->exportService->exportTransactionsCsv($userId, $filters, (string) $clientIp);

            // Send headers for file download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $res['filename'] . '"');
            header('X-Content-Row-Count: ' . (string) $res['rowCount']);
            header('X-Content-SHA256: ' . $res['fileHash']);
            header('Cache-Control: no-cache, no-store, must-revalidate');

            echo $res['content'];
            return;
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('EXPORT_ERROR', $e->getMessage(), 500);
        }
    }
}
