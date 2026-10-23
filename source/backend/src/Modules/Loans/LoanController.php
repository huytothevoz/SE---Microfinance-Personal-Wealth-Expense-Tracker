<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Loans;

use InvalidArgumentException;
use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class LoanController
{
    public function __construct(private readonly ?LoanService $service = null)
    {
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $loans = $this->loanService()->listLoans(
                $userId,
                $request->getQuery('direction'),
                $request->getQuery('status')
            );
            JsonResponse::success(['loans' => $loans, 'count' => count($loans)]);
        } catch (Throwable $error) {
            $this->error($error);
        }
    }

    public function show(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(['loan' => $this->loanService()->getLoan($id, $userId)]);
        } catch (Throwable $error) {
            $this->error($error);
        }
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(
                $this->loanService()->createLoan($userId, $request->getBody(), $request->getIdempotencyKey()),
                201
            );
        } catch (Throwable $error) {
            $this->error($error);
        }
    }

    public function payment(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(
                $this->loanService()->recordPayment(
                    $id,
                    $userId,
                    $request->getBody(),
                    $request->getIdempotencyKey()
                )
            );
        } catch (Throwable $error) {
            $this->error($error);
        }
    }

    private function loanService(): LoanService
    {
        return $this->service ?? new LoanService();
    }

    private function error(Throwable $error): void
    {
        if ($error instanceof ApiException) {
            JsonResponse::error($error->errorCode, $error->getMessage(), $error->status, $error->fields);
            return;
        }
        if ($error instanceof InvalidArgumentException) {
            JsonResponse::error('VALIDATION_ERROR', $error->getMessage(), 422);
            return;
        }
        error_log('Loan operation failed; type=' . get_class($error));
        JsonResponse::error('INTERNAL_ERROR', 'Không thể xử lý dữ liệu khoản vay.', 500);
    }
}
