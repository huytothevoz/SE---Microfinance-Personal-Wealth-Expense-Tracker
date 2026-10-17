<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;
use WealthFlow\Modules\Budgets\BudgetService;

final class TransactionController
{
    private LedgerService $ledgerService;
    private LedgerDAO $txRepo;
    private CategoryDAO $catRepo;
    private ExchangeRateService $fxService;
    private BudgetService $budgetService;

    public function __construct(
        ?LedgerService $ledgerService = null,
        ?LedgerDAO $txRepo = null,
        ?CategoryDAO $catRepo = null,
        ?ExchangeRateService $fxService = null,
        ?BudgetService $budgetService = null
    ) {
        $this->ledgerService = $ledgerService ?? new LedgerService();
        $this->txRepo = $txRepo ?? new LedgerDAO();
        $this->catRepo = $catRepo ?? new CategoryDAO();
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
        $type = strtoupper((string) ($body['type'] ?? 'EXPENSE'));
        if (!in_array($type, ['EXPENSE', 'INCOME'], true)) {
            JsonResponse::error('INVALID_TYPE', 'Loại giao dịch không hợp lệ. Vui lòng chọn EXPENSE hoặc INCOME.', 400);
            return;
        }

        try {
            $execution = $this->ledgerService->executeIdempotently(
                $userId,
                $idempotencyKey,
                $body,
                201,
                function () use ($type, $userId, $body): array {
                    if ($type === 'EXPENSE') {
                        $result = $this->ledgerService->postExpense($userId, $body);
                        $occurredOn = $body['occurredOn'] ?? date('Y-m-d');
                        $categoryId = (int) ($body['categoryId'] ?? 0);
                        $budgetCheck = $this->budgetService->checkBudgetAndNotify($userId, $categoryId, $occurredOn);
                        if ($budgetCheck !== null) {
                            $result['budgetStatus'] = $budgetCheck;
                        }
                        return $result;
                    }
                    return $this->ledgerService->postIncome($userId, $body);
                }
            );
            JsonResponse::success($execution['response'], $execution['code']);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            if ($e->getCode() === 409) {
                JsonResponse::error('IDEMPOTENCY_CONFLICT', $e->getMessage(), 409);
                return;
            }
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
            $execution = $this->ledgerService->executeIdempotently(
                $userId,
                $idempotencyKey,
                $body,
                200,
                fn (): array => $this->ledgerService->postTransfer($userId, $body)
            );
            JsonResponse::success($execution['response'], $execution['code']);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            if ($e->getCode() === 409) {
                JsonResponse::error('IDEMPOTENCY_CONFLICT', $e->getMessage(), 409);
                return;
            }
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
        $payload = ['txId' => $id, 'reason' => $reason];

        try {
            $execution = $this->ledgerService->executeIdempotently(
                $userId,
                $idempotencyKey,
                $payload,
                200,
                fn (): array => $this->ledgerService->reverseTransaction($userId, $id, $reason)
            );
            JsonResponse::success($execution['response'], $execution['code']);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        } catch (RuntimeException $e) {
            if ($e->getCode() === 409) {
                JsonResponse::error('IDEMPOTENCY_CONFLICT', $e->getMessage(), 409);
                return;
            }
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
        AuthContext::requireAdmin(AuthContext::getUserId($request));
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

    public function updateCategory(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);
        $body = $request->getBody();
        $name = trim((string) ($body['name'] ?? ''));

        if ($name === '') {
            JsonResponse::error('VALIDATION_ERROR', 'Tên danh mục không được để trống.', 422);
            return;
        }

        try {
            $this->catRepo->update($id, $userId, $name);
            JsonResponse::success(['id' => $id, 'name' => $name, 'message' => 'Cập nhật danh mục thành công.']);
        } catch (Exception $e) {
            JsonResponse::error('SERVER_ERROR', 'Không thể cập nhật danh mục: ' . $e->getMessage(), 500);
        }
    }

    public function archiveCategory(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);
        $this->catRepo->archive($id, $userId);
        JsonResponse::success(['message' => 'Đã lưu trữ danh mục thành công.']);
    }
}

