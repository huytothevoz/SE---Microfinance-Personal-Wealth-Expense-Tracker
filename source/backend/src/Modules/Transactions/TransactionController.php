<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use WealthFlow\Http\JsonResponse;
use WealthFlow\Http\Request;
use WealthFlow\Modules\Auth\AuthContext;
use WealthFlow\Modules\Budgets\BudgetService;

final class TransactionController
{
    private LedgerService $ledgerService;
    private TransactionRepository $txRepo;
    private CategoryRepository $catRepo;
    private ExchangeRateService $fxService;
    private BudgetService $budgetService;

    public function __construct(
        ?LedgerService $ledgerService = null,
        ?TransactionRepository $txRepo = null,
        ?CategoryRepository $catRepo = null,
        ?ExchangeRateService $fxService = null,
        ?BudgetService $budgetService = null
    ) {
        $this->ledgerService = $ledgerService ?? new LedgerService();
        $this->txRepo = $txRepo ?? new TransactionRepository();
        $this->catRepo = $catRepo ?? new CategoryRepository();
        $this->fxService = $fxService ?? new ExchangeRateService();
        $this->budgetService = $budgetService ?? new BudgetService();
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $filters = [
            'type' => $request->getQuery('type'),
            'status' => $request->getQuery('status'),
            'categoryId' => $request->getQuery('categoryId'),
            'startDate' => $request->getQuery('startDate'),
            'endDate' => $request->getQuery('endDate'),
            'limit' => $request->getQuery('limit', 50),
            'offset' => $request->getQuery('offset', 0),
        ];

        $transactions = $this->txRepo->listTransactions($userId, $filters);
        JsonResponse::success(['transactions' => $transactions, 'count' => count($transactions)]);
    }

    public function show(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);
        $tx = $this->txRepo->findByIdAndUser($id, $userId);

        if (!$tx) {
            JsonResponse::error('NOT_FOUND', 'Giao dịch không tồn tại.', 404);
            return;
        }

        JsonResponse::success(['transaction' => $tx]);
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $body = $request->getBody();
        $idempotencyKey = $request->getIdempotencyKey();

        // BR13: Idempotency check
        try {
            $idempCheck = $this->ledgerService->checkIdempotency($userId, $idempotencyKey, $body);
            if ($idempCheck['cached']) {
                JsonResponse::success($idempCheck['response'] ?? [], $idempCheck['code'] ?? 200);
                return;
            }
        } catch (RuntimeException $e) {
            JsonResponse::error('IDEMPOTENCY_CONFLICT', $e->getMessage(), 409);
            return;
        }

        $type = strtoupper((string) ($body['type'] ?? 'EXPENSE'));

        try {
            if ($type === 'EXPENSE') {
                $result = $this->ledgerService->postExpense($userId, $body);
                // FR05 & BR08: Check budget threshold and trigger notifications
                $occurredOn = $body['occurredOn'] ?? date('Y-m-d');
                $categoryId = (int) ($body['categoryId'] ?? 0);
                $budgetCheck = $this->budgetService->checkBudgetAndNotify($userId, $categoryId, $occurredOn);
                if ($budgetCheck !== null) {
                    $result['budgetStatus'] = $budgetCheck;
                }
            } elseif ($type === 'INCOME') {
                $result = $this->ledgerService->postIncome($userId, $body);
            } else {
                JsonResponse::error('INVALID_TYPE', 'Loại giao dịch không hợp lệ. Vui lòng chọn EXPENSE hoặc INCOME.', 400);
                return;
            }

            // Save idempotency on success
            $this->ledgerService->recordIdempotency($userId, $idempotencyKey, $body, (int) $result['transactionId'], 201, $result);
            JsonResponse::success($result, 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            JsonResponse::error('BUSINESS_RULE_VIOLATION', $e->getMessage(), $status);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', 'Đã xảy ra lỗi khi ghi nhận giao dịch: ' . $e->getMessage(), 500);
        }
    }

    public function transfer(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $body = $request->getBody();
        $idempotencyKey = $request->getIdempotencyKey();

        try {
            $idempCheck = $this->ledgerService->checkIdempotency($userId, $idempotencyKey, $body);
            if ($idempCheck['cached']) {
                JsonResponse::success($idempCheck['response'] ?? [], $idempCheck['code'] ?? 200);
                return;
            }
        } catch (RuntimeException $e) {
            JsonResponse::error('IDEMPOTENCY_CONFLICT', $e->getMessage(), 409);
            return;
        }

        try {
            $result = $this->ledgerService->postTransfer($userId, $body);
            $this->ledgerService->recordIdempotency($userId, $idempotencyKey, $body, (int) $result['transactionId'], 200, $result);
            JsonResponse::success($result, 200);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            JsonResponse::error('BUSINESS_RULE_VIOLATION', $e->getMessage(), $status);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', 'Lỗi chuyển tiền nội bộ: ' . $e->getMessage(), 500);
        }
    }

    public function reverse(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);
        $body = $request->getBody();
        $reason = trim((string) ($body['reason'] ?? 'Điều chỉnh sai sót'));
        $idempotencyKey = $request->getIdempotencyKey();

        try {
            $idempCheck = $this->ledgerService->checkIdempotency($userId, $idempotencyKey, ['txId' => $id, 'reason' => $reason]);
            if ($idempCheck['cached']) {
                JsonResponse::success($idempCheck['response'] ?? [], $idempCheck['code'] ?? 200);
                return;
            }
        } catch (RuntimeException $e) {
            JsonResponse::error('IDEMPOTENCY_CONFLICT', $e->getMessage(), 409);
            return;
        }

        try {
            $result = $this->ledgerService->reverseTransaction($userId, $id, $reason);
            $this->ledgerService->recordIdempotency($userId, $idempotencyKey, ['txId' => $id, 'reason' => $reason], (int) $result['reversalTransactionId'], 200, $result);
            JsonResponse::success($result, 200);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 422;
            JsonResponse::error('REVERSAL_ERROR', $e->getMessage(), $status);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', 'Lỗi đảo giao dịch: ' . $e->getMessage(), 500);
        }
    }

    public function listCategories(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $type = $request->getQuery('type');
        $categories = $this->catRepo->findAllByUser($userId, $type ? (string) $type : null);
        JsonResponse::success(['categories' => $categories]);
    }

    public function createCategory(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $body = $request->getBody();
        $name = trim((string) ($body['name'] ?? ''));
        $type = strtoupper((string) ($body['type'] ?? 'EXPENSE'));

        if ($name === '') {
            JsonResponse::error('VALIDATION_ERROR', 'Tên danh mục không được để trống.', 422);
            return;
        }
        if (!in_array($type, ['INCOME', 'EXPENSE'], true)) {
            JsonResponse::error('VALIDATION_ERROR', 'Loại danh mục phải là INCOME hoặc EXPENSE.', 422);
            return;
        }

        try {
            $id = $this->catRepo->create($userId, $name, $type);
            JsonResponse::success(['id' => $id, 'name' => $name, 'type' => $type], 201);
        } catch (Exception $e) {
            JsonResponse::error('DUPLICATE_CATEGORY', 'Danh mục đã tồn tại: ' . $e->getMessage(), 409);
        }
    }

    public function listRates(Request $request): void
    {
        $rates = $this->fxService->getAllLatestRates();
        JsonResponse::success(['rates' => $rates]);
    }

    public function recordRate(Request $request): void
    {
        $body = $request->getBody();
        $from = (string) ($body['from'] ?? '');
        $to = (string) ($body['to'] ?? 'VND');
        $rate = (float) ($body['rate'] ?? 0);
        $provider = (string) ($body['provider'] ?? 'MANUAL');

        try {
            $id = $this->fxService->recordRate($from, $to, $rate, $provider);
            JsonResponse::success(['id' => $id, 'from' => $from, 'to' => $to, 'rate' => $rate], 201);
        } catch (Exception $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
