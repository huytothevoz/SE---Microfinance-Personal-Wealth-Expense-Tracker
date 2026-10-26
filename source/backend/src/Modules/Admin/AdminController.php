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

    /**
     * GET /api/admin/overview (FR14.01, BR11)
     */
    public function overview(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            AuthContext::requireAdmin($adminUserId);
            $data = $this->adminService->getOverview();

            JsonResponse::success($data);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/users (FR14.01, BR11)
     */
    public function listUsers(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            AuthContext::requireAdmin($adminUserId);
            $filters = [
                'role' => $request->getQuery('role'),
                'status' => $request->getQuery('status'),
                'search' => $request->getQuery('search') ?? $request->getQuery('q'),
            ];
            $users = $this->adminService->listUsers($filters);
            $overview = $this->adminService->getOverview();

            JsonResponse::success([
                'users' => $users,
                'count' => count($users),
                'stats' => $overview,
            ]);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/admin/users/{id}/lock (FR14.03, SQ08)
     */
    public function lockUser(Request $request, int $id): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            AuthContext::requireAdmin($adminUserId);
            $body = $request->getBody();
            $reason = (string) ($body['reason'] ?? '');
            $result = $this->adminService->lockUser($adminUserId, $id, $reason);

            JsonResponse::success($result);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/admin/users/{id}/unlock (FR14.04)
     */
    public function unlockUser(Request $request, int $id): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            AuthContext::requireAdmin($adminUserId);
            $body = $request->getBody();
            $reason = isset($body['reason']) ? (string) $body['reason'] : null;
            $result = $this->adminService->unlockUser($adminUserId, $id, $reason);

            JsonResponse::success($result);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/audit-logs (FR14.03, FR14.04)
     */
    public function listAuditLogs(Request $request): void
    {
        try {
            $adminUserId = AuthContext::getUserId($request);
            AuthContext::requireAdmin($adminUserId);
            $limit = max(1, min(200, (int) $request->getQuery('limit', 50)));
            $offset = max(0, (int) $request->getQuery('offset', 0));
            $logs = $this->adminService->getAuditLogs($limit, $offset);

            JsonResponse::success([
                'auditLogs' => $logs,
                'count' => count($logs),
            ]);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable $e) {
            JsonResponse::error('INTERNAL_ERROR', $e->getMessage(), 500);
        }
    }
}
