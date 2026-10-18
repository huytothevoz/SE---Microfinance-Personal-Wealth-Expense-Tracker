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
    private CategoryService $categoryService;
    private ExchangeRateService $fxService;
    private BudgetService $budgetService;

    public function __construct(
        ?LedgerService $ledgerService = null,
        ?LedgerDAO $txRepo = null,
        ?CategoryDAO $catRepo = null,
        ?ExchangeRateService $fxService = null,
        ?BudgetService $budgetService = null,
        ?CategoryService $categoryService = null
    ) {
        $this->ledgerService = $ledgerService ?? new LedgerService();
        $this->txRepo = $txRepo ?? new LedgerDAO();
        $this->catRepo = $catRepo ?? new CategoryDAO();
        $this->fxService = $fxService ?? new ExchangeRateService();
        $this->budgetService = $budgetService ?? new BudgetService();
        $this->categoryService = $categoryService ?? new CategoryService($this->catRepo);
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $filters = $this->validatedListFilters($request);
            $transactions = $this->txRepo->listTransactions($userId, $filters);
            JsonResponse::success([
                'transactions' => $transactions,
                'count' => count($transactions),
                'limit' => $filters['limit'],
                'offset' => $filters['offset'],
            ]);
        } catch (InvalidArgumentException $error) {
            JsonResponse::error('VALIDATION_ERROR', $error->getMessage(), 422);
        }
    }

    public function show(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $tx = $this->txRepo->findByIdAndUser($id, $userId);

        if (!$tx) {
            JsonResponse::error('NOT_FOUND', 'Giao dịch không tồn tại.', 404);
            return;
        }

        JsonResponse::success(['transaction' => $tx]);
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
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
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
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
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
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
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $type = $request->getQuery('type');
        $includeArchived = strtolower((string) $request->getQuery('includeArchived', 'false')) === 'true';
        try {
            JsonResponse::success($this->categoryService->list(
                $userId,
                $type === null ? null : (string) $type,
                $includeArchived
            ));
        } catch (InvalidArgumentException $error) {
            JsonResponse::error('VALIDATION_ERROR', $error->getMessage(), 422);
        }
    }

    public function createCategory(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $body = $request->getBody();
        try {
            $category = $this->categoryService->create(
                $userId,
                (string) ($body['name'] ?? ''),
                (string) ($body['type'] ?? 'EXPENSE')
            );
            JsonResponse::success(['category' => $category], 201);
        } catch (InvalidArgumentException $error) {
            JsonResponse::error('VALIDATION_ERROR', $error->getMessage(), 422);
        } catch (RuntimeException $error) {
            JsonResponse::error('DUPLICATE_CATEGORY', $error->getMessage(), $error->getCode() ?: 409);
        } catch (Exception $error) {
            JsonResponse::error('DUPLICATE_CATEGORY', 'Danh mục đã tồn tại.', 409);
        }
    }

    public function listRates(Request $request): void
    {
        AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $rates = $this->fxService->getAllLatestRates();
        JsonResponse::success(['rates' => $rates]);
    }

    public function syncRates(Request $request): void
    {
        AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success($this->fxService->syncPublicRates());
        } catch (RuntimeException $error) {
            JsonResponse::error('RATE_PROVIDER_UNAVAILABLE', $error->getMessage(), 503);
        }
    }

    public function recordRate(Request $request): void
    {
        AuthContext::requireAdmin(AuthContext::getUserId($request));
        $body = $request->getBody();
        $from = (string) ($body['from'] ?? '');
        $to = (string) ($body['to'] ?? 'VND');
        $rate = trim((string) ($body['rate'] ?? ''));
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
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $body = $request->getBody();
        try {
            $category = $this->categoryService->rename($userId, $id, (string) ($body['name'] ?? ''));
            JsonResponse::success(['category' => $category, 'message' => 'Cập nhật danh mục thành công.']);
        } catch (InvalidArgumentException $error) {
            JsonResponse::error('VALIDATION_ERROR', $error->getMessage(), 422);
        } catch (RuntimeException $error) {
            $status = $error->getCode() === 404 ? 404 : 409;
            JsonResponse::error($status === 404 ? 'NOT_FOUND' : 'DUPLICATE_CATEGORY', $error->getMessage(), $status);
        } catch (Exception $error) {
            JsonResponse::error('DUPLICATE_CATEGORY', 'Không thể đổi tên vì danh mục đã tồn tại.', 409);
        }
    }

    public function archiveCategory(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $result = $this->categoryService->archive($userId, $id);
            JsonResponse::success($result + ['message' => 'Đã lưu trữ danh mục; lịch sử giao dịch được giữ nguyên.']);
        } catch (RuntimeException $error) {
            $status = $error->getCode() === 404 ? 404 : 409;
            JsonResponse::error($status === 404 ? 'NOT_FOUND' : 'ARCHIVE_FAILED', $error->getMessage(), $status);
        }
    }

    /** @return array<string, mixed> */
    private function validatedListFilters(Request $request): array
    {
        $type = strtoupper(trim((string) $request->getQuery('type', '')));
        $status = strtoupper(trim((string) $request->getQuery('status', '')));
        $categoryId = trim((string) $request->getQuery('categoryId', ''));
        $startDate = trim((string) $request->getQuery('startDate', ''));
        $endDate = trim((string) $request->getQuery('endDate', ''));

        if ($type !== '' && !in_array($type, ['INCOME', 'EXPENSE', 'TRANSFER'], true)) {
            throw new InvalidArgumentException('Bộ lọc loại giao dịch không hợp lệ.');
        }
        if ($status !== '' && !in_array($status, ['DRAFT', 'POSTED', 'REVERSED'], true)) {
            throw new InvalidArgumentException('Bộ lọc trạng thái giao dịch không hợp lệ.');
        }
        if ($categoryId !== '' && (!ctype_digit($categoryId) || (int) $categoryId < 1)) {
            throw new InvalidArgumentException('Mã danh mục lọc không hợp lệ.');
        }
        foreach (['Ngày bắt đầu' => $startDate, 'Ngày kết thúc' => $endDate] as $label => $date) {
            if ($date !== '' && !$this->isValidDate($date)) {
                throw new InvalidArgumentException("{$label} không đúng định dạng YYYY-MM-DD.");
            }
        }
        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            throw new InvalidArgumentException('Ngày bắt đầu không được sau ngày kết thúc.');
        }

        return [
            'type' => $type,
            'status' => $status,
            'categoryId' => $categoryId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'limit' => max(1, min(100, (int) $request->getQuery('limit', 50))),
            'offset' => max(0, (int) $request->getQuery('offset', 0)),
        ];
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
