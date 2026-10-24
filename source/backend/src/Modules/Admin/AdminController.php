<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Admin;

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
     * GET /api/admin/overview (UC12 / FR14.01 / BR11).
     */
    public function overview(Request $request): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        JsonResponse::success($this->adminService->getOverview());
    }

    /**
     * GET /api/admin/users (UC12 / FR14.01 / BR11).
     */
    public function listUsers(Request $request): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
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
    }

    /**
     * PUT /api/admin/users/{id}/lock (UC12 / FR14.03 / SQ08).
     */
    public function lockUser(Request $request, int $id): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $body = $request->getBody();
        $reason = (string) ($body['reason'] ?? '');
        $result = $this->adminService->lockUser($adminId, $id, $reason);
        JsonResponse::success($result);
    }

    /**
     * PUT /api/admin/users/{id}/unlock (UC12 / FR14.04).
     */
    public function unlockUser(Request $request, int $id): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $body = $request->getBody();
        $reason = isset($body['reason']) ? (string) $body['reason'] : null;
        $result = $this->adminService->unlockUser($adminId, $id, $reason);
        JsonResponse::success($result);
    }

    /**
     * GET /api/admin/categories or GET /api/admin/default-categories (FR13.01).
     */
    public function listCategories(Request $request): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $type = $request->getQuery('type') ?? $request->getQuery('categoryType');
        $includeInactiveRaw = $request->getQuery('includeInactive', '1');
        $includeInactive = !in_array(strtolower((string) $includeInactiveRaw), ['0', 'false', 'no'], true);

        $categories = $this->adminService->listDefaultCategories(
            $type !== null ? (string) $type : null,
            $includeInactive
        );
        JsonResponse::success([
            'categories' => $categories,
            'defaultCategories' => $categories,
            'count' => count($categories),
        ]);
    }

    /**
     * POST /api/admin/categories or POST /api/admin/default-categories (FR13.01).
     */
    public function createCategory(Request $request): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $category = $this->adminService->createDefaultCategory($adminId, $request->getBody());
        JsonResponse::success(['category' => $category], 201);
    }

    /**
     * PUT /api/admin/categories/{id} or PUT /api/admin/default-categories/{id} (FR13.01).
     */
    public function updateCategory(Request $request, int $id): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $category = $this->adminService->updateDefaultCategory($adminId, $id, $request->getBody());
        JsonResponse::success(['category' => $category]);
    }

    /**
     * GET /api/admin/system-config (FR13.02).
     */
    public function getSystemConfig(Request $request): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        JsonResponse::success($this->adminService->getSystemConfig());
    }

    /**
     * PUT /api/admin/system-config or PUT /api/admin/system-config/{key} (FR13.02).
     */
    public function updateSystemConfig(Request $request, ?string $key = null): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $result = $this->adminService->updateSystemConfig($adminId, $request->getBody(), $key);
        JsonResponse::success($result);
    }

    /**
     * GET /api/admin/announcements (FR13.03).
     */
    public function listAnnouncements(Request $request): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $limit = max(1, min(100, (int) $request->getQuery('limit', 30)));
        $announcements = $this->adminService->listAnnouncements($limit);
        JsonResponse::success([
            'announcements' => $announcements,
            'count' => count($announcements),
        ]);
    }

    /**
     * POST /api/admin/announcements (FR13.03, SQ08).
     */
    public function broadcastAnnouncement(Request $request): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $result = $this->adminService->broadcastAnnouncement($adminId, $request->getBody());
        JsonResponse::success($result, 201);
    }

    /**
     * GET /api/admin/audit-logs (FR13.02, FR13.03, FR14.03, FR14.04).
     */
    public function listAuditLogs(Request $request): void
    {
        $adminId = AuthContext::getUserId($request);
        AuthContext::requireAdmin($adminId);
        $limit = max(1, min(200, (int) $request->getQuery('limit', 50)));
        $offset = max(0, (int) $request->getQuery('offset', 0));
        $logs = $this->adminService->getAuditLogs($limit, $offset);
        JsonResponse::success([
            'auditLogs' => $logs,
            'count' => count($logs),
        ]);
    }
}
