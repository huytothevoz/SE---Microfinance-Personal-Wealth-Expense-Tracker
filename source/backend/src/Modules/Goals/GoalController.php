<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Goals;

use Throwable;
use InvalidArgumentException;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class GoalController
{
    private GoalService $goalService;

    public function __construct(?GoalService $goalService = null)
    {
        $this->goalService = $goalService ?? new GoalService();
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $goals = $this->goalService->listGoals($userId, $request->getQuery('status'));
            JsonResponse::success(['goals' => $goals, 'count' => count($goals)]);
        } catch (Throwable $error) {
            $this->error($error, 'Không thể tải mục tiêu tiết kiệm.');
        }
    }

    public function show(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(['goal' => $this->goalService->getGoal($id, $userId)]);
        } catch (Throwable $error) {
            $this->error($error, 'Không thể tải mục tiêu này.');
        }
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(['goal' => $this->goalService->createGoal($userId, $request->getBody())], 201);
        } catch (Throwable $error) {
            $this->error($error, 'Không thể tạo mục tiêu.');
        }
    }

    public function update(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(['goal' => $this->goalService->updateGoal($id, $userId, $request->getBody())]);
        } catch (Throwable $error) {
            $this->error($error, 'Không thể cập nhật mục tiêu.');
        }
    }

    public function deposit(Request $request, int $id): void
    {
        $this->allocation($request, $id, 'DEPOSIT');
    }

    public function withdraw(Request $request, int $id): void
    {
        $this->allocation($request, $id, 'WITHDRAWAL');
    }

    public function complete(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(['goal' => $this->goalService->completeGoal($id, $userId)]);
        } catch (Throwable $error) {
            $this->error($error, 'Không thể hoàn thành mục tiêu.');
        }
    }

    public function delete(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $this->goalService->cancelGoal($id, $userId);
            JsonResponse::success(['message' => 'Đã hủy mục tiêu và hoàn lại số tiền đã để dành về ví.']);
        } catch (Throwable $error) {
            $this->error($error, 'Không thể hủy mục tiêu.');
        }
    }

    private function allocation(Request $request, int $id, string $type): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(['goal' => $this->goalService->allocate(
                $id,
                $userId,
                $type,
                $request->getBody(),
                $request->getIdempotencyKey()
            )]);
        } catch (Throwable $error) {
            $this->error($error, $type === 'DEPOSIT' ? 'Không thể thêm tiền để dành.' : 'Không thể lấy tiền khỏi mục tiêu.');
        }
    }

    private function error(Throwable $error, string $fallback): void
    {
        if ($error instanceof ApiException) {
            JsonResponse::error($error->errorCode, $error->getMessage(), $error->status, $error->fields);
            return;
        }
        if ($error instanceof InvalidArgumentException) {
            JsonResponse::error('VALIDATION_ERROR', $error->getMessage(), 422);
            return;
        }
        JsonResponse::error('INTERNAL_ERROR', $fallback, 500);
    }
}
