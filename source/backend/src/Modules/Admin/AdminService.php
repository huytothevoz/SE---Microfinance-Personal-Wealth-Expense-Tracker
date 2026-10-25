<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Admin;

use InvalidArgumentException;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;

final class AdminService
{
    private AdminDAO $adminDao;
    private AuditLogDAO $auditLogDao;

    public function __construct(?AdminDAO $adminDao = null, ?AuditLogDAO $auditLogDao = null)
    {
        $this->adminDao = $adminDao ?? new AdminDAO();
        $this->auditLogDao = $auditLogDao ?? new AuditLogDAO();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDefaultCategories(int $adminUserId): array
    {
        AuthContext::requireAdmin($adminUserId);
        return $this->adminDao->getAllDefaultCategories();
    }

    /**
     * @param array<string, mixed> $dto
     * @return array<string, mixed>
     */
    public function createDefaultCategory(int $adminUserId, array $dto): array
    {
        AuthContext::requireAdmin($adminUserId);

        $name = trim((string) ($dto['name'] ?? ''));
        $categoryType = strtoupper(trim((string) ($dto['categoryType'] ?? $dto['type'] ?? '')));
        $isActive = isset($dto['isActive']) ? (bool) $dto['isActive'] : true;

        if ($name === '') {
            throw new ApiException('VALIDATION_ERROR', 'Tên danh mục không được để trống.', 422, ['name' => 'Tên danh mục là bắt buộc']);
        }
        if (mb_strlen($name) > 100) {
            throw new ApiException('VALIDATION_ERROR', 'Tên danh mục không được vượt quá 100 ký tự.', 422, ['name' => 'Độ dài tối đa 100 ký tự']);
        }
        if (!in_array($categoryType, ['INCOME', 'EXPENSE'], true)) {
            throw new ApiException('VALIDATION_ERROR', 'Loại danh mục phải là INCOME hoặc EXPENSE.', 422, ['categoryType' => 'Loại không hợp lệ']);
        }

        // Kiểm tra trùng tên và loại theo FR13.01 (409 Conflict)
        $existing = $this->adminDao->findDefaultCategoryByNameAndType($name, $categoryType);
        if ($existing) {
            throw new ApiException('CATEGORY_ALREADY_EXISTS', 'Danh mục mẫu với tên và loại này đã tồn tại trong hệ thống.', 409);
        }

        $id = $this->adminDao->createDefaultCategory($name, $categoryType, $isActive);

        // Ghi nhật ký kiểm toán quản trị
        $this->auditLogDao->logAdminAction(
            $adminUserId,
            'CREATE_DEFAULT_CATEGORY',
            null,
            "Tạo danh mục mẫu: {$name} ({$categoryType})"
        );

        return [
            'id' => $id,
            'name' => $name,
            'categoryType' => $categoryType,
            'isActive' => $isActive,
        ];
    }

    /**
     * @param array<string, mixed> $dto
     * @return array<string, mixed>
     */
    public function updateDefaultCategory(int $adminUserId, int $id, array $dto): array
    {
        AuthContext::requireAdmin($adminUserId);

        $existing = $this->adminDao->findDefaultCategoryById($id);
        if (!$existing) {
            throw new ApiException('NOT_FOUND', 'Danh mục mẫu không tồn tại.', 404);
        }

        $fields = [];
        $newName = isset($dto['name']) ? trim((string) $dto['name']) : null;
        $newType = isset($dto['categoryType']) || isset($dto['type'])
            ? strtoupper(trim((string) ($dto['categoryType'] ?? $dto['type'])))
            : null;

        if ($newName !== null) {
            if ($newName === '') {
                throw new ApiException('VALIDATION_ERROR', 'Tên danh mục không được để trống.', 422, ['name' => 'Tên danh mục là bắt buộc']);
            }
            if (mb_strlen($newName) > 100) {
                throw new ApiException('VALIDATION_ERROR', 'Tên danh mục không được vượt quá 100 ký tự.', 422, ['name' => 'Độ dài tối đa 100 ký tự']);
            }
            $fields['name'] = $newName;
        }

        if ($newType !== null) {
            if (!in_array($newType, ['INCOME', 'EXPENSE'], true)) {
                throw new ApiException('VALIDATION_ERROR', 'Loại danh mục phải là INCOME hoặc EXPENSE.', 422, ['categoryType' => 'Loại không hợp lệ']);
            }
            $fields['categoryType'] = $newType;
        }

        if (array_key_exists('isActive', $dto)) {
            $fields['isActive'] = (bool) $dto['isActive'];
        }

        // Kiểm tra trùng nếu đổi tên hoặc loại
        $checkName = $fields['name'] ?? $existing['name'];
        $checkType = $fields['categoryType'] ?? $existing['categoryType'];
        $dup = $this->adminDao->findDefaultCategoryByNameAndType($checkName, $checkType, $id);
        if ($dup) {
            throw new ApiException('CATEGORY_ALREADY_EXISTS', 'Danh mục mẫu với tên và loại này đã tồn tại trong hệ thống.', 409);
        }

        $this->adminDao->updateDefaultCategory($id, $fields);

        // Ghi nhật ký kiểm toán quản trị
        $this->auditLogDao->logAdminAction(
            $adminUserId,
            'UPDATE_DEFAULT_CATEGORY',
            null,
            "Cập nhật danh mục mẫu ID {$id}: " . json_encode($fields, JSON_UNESCAPED_UNICODE)
        );

        return $this->adminDao->findDefaultCategoryById($id) ?? [];
    }

    public function deleteDefaultCategory(int $adminUserId, int $id): bool
    {
        AuthContext::requireAdmin($adminUserId);

        $existing = $this->adminDao->findDefaultCategoryById($id);
        if (!$existing) {
            throw new ApiException('NOT_FOUND', 'Danh mục mẫu không tồn tại.', 404);
        }

        $deleted = $this->adminDao->deleteDefaultCategory($id);
        if ($deleted) {
            $this->auditLogDao->logAdminAction(
                $adminUserId,
                'DELETE_DEFAULT_CATEGORY',
                null,
                "Xóa danh mục mẫu ID {$id} ({$existing['name']})"
            );
        }

        return $deleted;
    }
}
