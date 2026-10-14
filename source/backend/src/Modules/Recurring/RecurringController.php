<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Recurring;

use Exception;
use InvalidArgumentException;
use WealthFlow\Http\JsonResponse;
use WealthFlow\Http\Request;
use WealthFlow\Modules\Auth\AuthContext;

final class RecurringController
{
    private RecurringService $service;
    private RecurringWorker $worker;

    public function __construct(
        ?RecurringService $service = null,
        ?RecurringWorker $worker = null
    ) {
        $this->service = $service ?? new RecurringService();
        $this->worker = $worker ?? new RecurringWorker();
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $schedules = $this->service->listSchedules($userId);
        JsonResponse::success([
            'schedules' => $schedules,
            'count' => count($schedules),
        ]);
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::getUserId($request);
        $body = $request->getBody();

        try {
            $schedule = $this->service->createSchedule($userId, $body);
            JsonResponse::success(['schedule' => $schedule], 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', 'Lỗi thiết lập giao dịch định kỳ: ' . $e->getMessage(), 500);
        }
    }

    public function pause(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);

        try {
            $this->service->pauseSchedule($id, $userId);
            JsonResponse::success(['message' => 'Đã tạm dừng khoản định kỳ.'], 200);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    public function resume(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);

        try {
            $this->service->resumeSchedule($id, $userId);
            JsonResponse::success(['message' => 'Đã kích hoạt lại khoản định kỳ.'], 200);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    public function delete(Request $request, int $id): void
    {
        $userId = AuthContext::getUserId($request);

        try {
            $this->service->deleteSchedule($id, $userId);
            JsonResponse::success(['message' => 'Đã xóa khoản định kỳ thành công.'], 200);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    public function processDue(Request $request): void
    {
        $asOfDate = (string) $request->get('asOfDate', date('Y-m-d'));
        try {
            $summary = $this->worker->processDueSchedules($asOfDate);
            JsonResponse::success([
                'message' => 'Đã quét và xử lý thành công các khoản định kỳ đến hạn.',
                'asOfDate' => $asOfDate,
                'result' => $summary,
            ], 200);
        } catch (Exception $e) {
            JsonResponse::error('INTERNAL_ERROR', 'Lỗi khi xử lý tiến trình định kỳ: ' . $e->getMessage(), 500);
        }
    }
}
