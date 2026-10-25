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
}
