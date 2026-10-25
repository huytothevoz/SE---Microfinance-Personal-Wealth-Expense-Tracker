<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Admin;

use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class AdminController
{
    private AdminService $adminService;

    public function __construct(?AdminService $adminService = null)
    {
        $this->adminService = $adminService ?? new AdminService();
    }

    /**
     * GET /api/admin/default-categories
     */
    public function listDefaultCategories(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            $categories = $this->adminService->getDefaultCategories($adminUserId);

            JsonResponse::success([
                'categories' => $categories,
                'count' => count($categories),
            ]);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/default-categories
     */
    public function createDefaultCategory(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            $dto = $request->getBody();
            $created = $this->adminService->createDefaultCategory($adminUserId, $dto);

            JsonResponse::success($created, 201);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/admin/default-categories/{id}
     */
    public function updateDefaultCategory(Request $request, int $id): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            $dto = $request->getBody();
            $updated = $this->adminService->updateDefaultCategory($adminUserId, $id, $dto);

            JsonResponse::success($updated, 200);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/admin/default-categories/{id}
     */
    public function deleteDefaultCategory(Request $request, int $id): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            $this->adminService->deleteDefaultCategory($adminUserId, $id);

            JsonResponse::success(['message' => 'Đã xóa danh mục mẫu thành công.']);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/system-config (FR13.02)
     */
    public function getSystemConfig(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            $data = $this->adminService->getSystemConfigs($adminUserId);

            JsonResponse::success($data);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/admin/system-config (FR13.02)
     */
    public function updateSystemConfig(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            $dto = $request->getBody();
            $data = $this->adminService->updateSystemConfigs($adminUserId, $dto);

            JsonResponse::success($data, 200);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/announcements (FR13.03, SQ08)
     */
    public function sendAnnouncement(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            $dto = $request->getBody();
            $result = $this->adminService->broadcastAnnouncement($adminUserId, $dto);

            JsonResponse::success($result, 201);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/announcements (FR13.03)
     */
    public function listAnnouncements(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            $limit = (int) $request->getQuery('limit', 20);
            $history = $this->adminService->getBroadcastHistory($adminUserId, $limit);

            JsonResponse::success([
                'announcements' => $history,
                'count' => count($history),
            ]);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }
}
