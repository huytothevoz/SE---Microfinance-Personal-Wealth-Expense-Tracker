<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Admin;

use InvalidArgumentException;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Modules\Notifications\NotificationDAO;

final class AdminService
{
    private AdminDAO $adminDao;
    private AuditLogDAO $auditLogDao;
    private NotificationDAO $notifDao;

    public function __construct(
        ?AdminDAO $adminDao = null,
        ?AuditLogDAO $auditLogDao = null,
        mixed $notifDaoOrService = null
    ) {
        $this->adminDao = $adminDao ?? new AdminDAO();
        $this->auditLogDao = $auditLogDao ?? new AuditLogDAO();
        if ($notifDaoOrService instanceof NotificationDAO) {
            $this->notifDao = $notifDaoOrService;
        } else {
            $this->notifDao = new NotificationDAO();
        }
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

    /**
     * Lấy cấu hình tham số vận hành hệ thống (FR13.02)
     *
     * @return array<string, mixed>
     */
    public function getSystemConfigs(int $adminUserId): array
    {
        AuthContext::requireAdmin($adminUserId);

        $defaults = [
            'BUDGET_WARNING_THRESHOLD_PERCENT' => '80',
            'JWT_ACCESS_TOKEN_TTL_MINUTES' => '60',
            'CSV_EXPORT_MAX_ROWS' => '5000',
            'LOAN_REMINDER_DAYS_BEFORE' => '3',
        ];

        $current = $this->adminDao->getSystemConfigs();
        $configs = array_merge($defaults, $current);

        return [
            'configs' => $configs,
            'definitions' => [
                'BUDGET_WARNING_THRESHOLD_PERCENT' => [
                    'label' => 'Ngưỡng cảnh báo chi tiêu ngân sách (%)',
                    'type' => 'integer',
                    'min' => 50,
                    'max' => 95,
                    'unit' => '%',
                    'description' => 'Khi mức chi tiêu đạt ngưỡng này, hệ thống sẽ gửi cảnh báo Vàng (Yellow Alert) cho người dùng.',
                ],
                'JWT_ACCESS_TOKEN_TTL_MINUTES' => [
                    'label' => 'Thời gian sống Token xác thực (Phút)',
                    'type' => 'integer',
                    'min' => 15,
                    'max' => 1440,
                    'unit' => 'phút',
                    'description' => 'Thời gian hiệu lực của JWT Access Token trước khi phiên làm việc cần làm mới.',
                ],
                'CSV_EXPORT_MAX_ROWS' => [
                    'label' => 'Giới hạn số dòng đối soát xuất CSV',
                    'type' => 'integer',
                    'min' => 100,
                    'max' => 50000,
                    'unit' => 'dòng',
                    'description' => 'Số lượng bản ghi giao dịch tối đa cho phép xuất trong một lần đối soát CSV.',
                ],
                'LOAN_REMINDER_DAYS_BEFORE' => [
                    'label' => 'Số ngày nhắc hạn khoản vay trước hạn',
                    'type' => 'integer',
                    'min' => 1,
                    'max' => 30,
                    'unit' => 'ngày',
                    'description' => 'Thời điểm hệ thống kích hoạt thông báo nhắc nợ trước ngày đến hạn thanh toán.',
                ],
            ],
            'auditLogs' => $this->auditLogDao->getAdminLogs(10),
        ];
    }

    /**
     * Cập nhật tham số vận hành hệ thống (FR13.02)
     *
     * @param array<string, mixed> $dto
     * @return array<string, mixed>
     */
    public function updateSystemConfigs(int $adminUserId, array $dto): array
    {
        AuthContext::requireAdmin($adminUserId);

        $fieldErrors = [];
        $allowedKeys = [
            'BUDGET_WARNING_THRESHOLD_PERCENT',
            'JWT_ACCESS_TOKEN_TTL_MINUTES',
            'CSV_EXPORT_MAX_ROWS',
            'LOAN_REMINDER_DAYS_BEFORE',
        ];

        if (array_key_exists('BUDGET_WARNING_THRESHOLD_PERCENT', $dto)) {
            $val = $dto['BUDGET_WARNING_THRESHOLD_PERCENT'];
            if (!is_numeric($val) || (int) $val < 50 || (int) $val > 95) {
                $fieldErrors['BUDGET_WARNING_THRESHOLD_PERCENT'] = 'Ngưỡng cảnh báo ngân sách phải là số nguyên từ 50% đến 95%.';
            }
        }

        if (array_key_exists('JWT_ACCESS_TOKEN_TTL_MINUTES', $dto)) {
            $val = $dto['JWT_ACCESS_TOKEN_TTL_MINUTES'];
            if (!is_numeric($val) || (int) $val < 15 || (int) $val > 1440) {
                $fieldErrors['JWT_ACCESS_TOKEN_TTL_MINUTES'] = 'Thời gian sống Token phải từ 15 phút đến 1440 phút (24 giờ).';
            }
        }

        if (array_key_exists('CSV_EXPORT_MAX_ROWS', $dto)) {
            $val = $dto['CSV_EXPORT_MAX_ROWS'];
            if (!is_numeric($val) || (int) $val < 100 || (int) $val > 50000) {
                $fieldErrors['CSV_EXPORT_MAX_ROWS'] = 'Số dòng xuất CSV tối đa phải từ 100 đến 50.000 dòng.';
            }
        }

        if (array_key_exists('LOAN_REMINDER_DAYS_BEFORE', $dto)) {
            $val = $dto['LOAN_REMINDER_DAYS_BEFORE'];
            if (!is_numeric($val) || (int) $val < 1 || (int) $val > 30) {
                $fieldErrors['LOAN_REMINDER_DAYS_BEFORE'] = 'Số ngày nhắc hạn khoản vay phải từ 1 đến 30 ngày.';
            }
        }

        if (!empty($fieldErrors)) {
            throw new ApiException('VALIDATION_ERROR', 'Tham số cấu hình không hợp lệ.', 422, $fieldErrors);
        }

        $updated = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $dto)) {
                $valStr = (string) (int) $dto[$key];
                $this->adminDao->updateSystemConfig($key, $valStr);
                $updated[$key] = $valStr;
            }
        }

        if (empty($updated)) {
            throw new ApiException('VALIDATION_ERROR', 'Không có tham số cấu hình hợp lệ nào được cung cấp để cập nhật.', 422);
        }

        // Ghi nhật ký kiểm toán hành động quản trị
        $this->auditLogDao->logAdminAction(
            $adminUserId,
            'UPDATE_SYSTEM_CONFIG',
            null,
            'Cập nhật tham số hệ thống: ' . json_encode($updated, JSON_UNESCAPED_UNICODE)
        );

        return $this->getSystemConfigs($adminUserId);
    }

    /**
     * Phát thông báo phát thanh (Broadcast) đến toàn bộ người dùng (FR13.03, SQ08)
     *
     * @param array<string, mixed> $dto
     * @return array<string, mixed>
     */
    public function broadcastAnnouncement(int $adminUserId, array $dto): array
    {
        AuthContext::requireAdmin($adminUserId);

        $fieldErrors = [];
        $title = trim((string) ($dto['title'] ?? ''));
        $body = trim((string) ($dto['body'] ?? ''));
        $priority = strtoupper(trim((string) ($dto['priority'] ?? 'INFO')));

        if ($title === '') {
            $fieldErrors['title'] = 'Tiêu đề thông báo không được để trống.';
        } elseif (mb_strlen($title) > 255) {
            $fieldErrors['title'] = 'Tiêu đề thông báo không được vượt quá 255 ký tự.';
        }

        if ($body === '') {
            $fieldErrors['body'] = 'Nội dung thông báo không được để trống.';
        } elseif (mb_strlen($body) > 2000) {
            $fieldErrors['body'] = 'Nội dung thông báo không được vượt quá 2000 ký tự.';
        }

        $allowedPriorities = ['INFO', 'WARNING', 'CRITICAL', 'MAINTENANCE', 'URGENT'];
        if (!in_array($priority, $allowedPriorities, true)) {
            $priority = 'INFO';
        }

        if (!empty($fieldErrors)) {
            throw new ApiException('VALIDATION_ERROR', 'Dữ liệu thông báo phát thanh không hợp lệ.', 422, $fieldErrors);
        }

        // Thực thi SQL Batch Fan-out O(1)
        $recipientCount = $this->notifDao->broadcast($title, $body, $priority);

        // Ghi nhật ký kiểm toán quản trị
        $this->auditLogDao->logAdminAction(
            $adminUserId,
            'BROADCAST_ANNOUNCEMENT',
            null,
            "Phát broadcast: {$title} | Mức độ: {$priority} | Người nhận: {$recipientCount}"
        );

        return [
            'title' => $title,
            'body' => $body,
            'priority' => $priority,
            'recipientCount' => $recipientCount,
            'status' => 'DISPATCHED',
            'message' => "Đã phát thông báo thành công tới {$recipientCount} người dùng.",
        ];
    }

    /**
     * Lấy lịch sử thông báo broadcast (FR13.03)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBroadcastHistory(int $adminUserId, int $limit = 20): array
    {
        AuthContext::requireAdmin($adminUserId);
        return $this->auditLogDao->getBroadcastLogs($limit);
    }

    /**
     * Macro overview dashboard statistics (FR14.01, FR14.02, BR11).
     *
     * @return array<string, mixed>
     */
    public function getOverview(): array
    {
        $stats = $this->adminDao->getOverviewStats();
        $stats['recentAuditLogs'] = $this->getAuditLogs(10, 0);
        return $stats;
    }

    /**
     * List users with macro status/role metadata (FR14.01, FR14.02, BR11).
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listUsers(array $filters = []): array
    {
        $rows = $this->adminDao->listUsers($filters);
        return array_map(fn(array $row): array => $this->presentUser($row), $rows);
    }

    /**
     * Lock a user account, immediately revoke all active sessions, and record audit log (FR14.03, SQ08).
     *
     * @return array<string, mixed>
     */
    public function lockUser(int $adminId, int $targetUserId, string $reason): array
    {
        $cleanReason = trim(preg_replace('/\s+/u', ' ', $reason) ?? '');
        if ($cleanReason === '' || mb_strlen($cleanReason, 'UTF-8') < 10) {
            throw new ApiException(
                'LOCK_REASON_REQUIRED',
                'Lý do khóa tài khoản là bắt buộc và phải có ít nhất 10 ký tự.',
                422,
                ['reason' => 'Vui lòng nhập lý do khóa tài khoản tối thiểu 10 ký tự.']
            );
        }

        if ($adminId === $targetUserId) {
            throw new ApiException(
                'CANNOT_LOCK_LAST_ADMIN',
                'Quản trị viên không thể tự khóa tài khoản của chính mình.',
                400
            );
        }

        $target = $this->adminDao->findUserById($targetUserId);
        if ($target === null) {
            throw new ApiException('USER_NOT_FOUND', 'Không tìm thấy tài khoản người dùng.', 404);
        }

        if (strtoupper((string) $target['role']) === 'ADMIN') {
            $otherActiveAdmins = $this->adminDao->countActiveAdminsExcept($targetUserId);
            if ($otherActiveAdmins < 1) {
                throw new ApiException(
                    'CANNOT_LOCK_LAST_ADMIN',
                    'Không thể khóa tài khoản Quản trị viên đang hoạt động cuối cùng của hệ thống.',
                    400
                );
            }
        }

        $pdo = $this->adminDao->getPdo();
        $ownsTx = !$pdo->inTransaction();
        if ($ownsTx) {
            $pdo->beginTransaction();
        }

        try {
            $this->adminDao->updateUserStatus($targetUserId, 'LOCKED');
            $revokedSessions = $this->adminDao->revokeUserSessions($targetUserId);
            $auditId = $this->auditLogDao->logAdminAction($adminId, 'LOCK_USER', $targetUserId, $cleanReason);

            if ($ownsTx) {
                $pdo->commit();
            }

            $updated = $this->adminDao->findUserById($targetUserId) ?? $target;
            return [
                'user' => $this->presentUser($updated),
                'revokedSessions' => $revokedSessions,
                'auditLogId' => $auditId,
                'reason' => $cleanReason,
            ];
        } catch (\Throwable $e) {
            if ($ownsTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Unlock a locked user account and record audit log (FR14.04).
     *
     * @return array<string, mixed>
     */
    public function unlockUser(int $adminId, int $targetUserId, ?string $reason = null): array
    {
        $target = $this->adminDao->findUserById($targetUserId);
        if ($target === null) {
            throw new ApiException('USER_NOT_FOUND', 'Không tìm thấy tài khoản người dùng.', 404);
        }

        $cleanReason = trim((string) ($reason ?? ''));
        if ($cleanReason === '') {
            $cleanReason = 'Mở khóa tài khoản theo quyết định của Quản trị viên.';
        }

        $pdo = $this->adminDao->getPdo();
        $ownsTx = !$pdo->inTransaction();
        if ($ownsTx) {
            $pdo->beginTransaction();
        }

        try {
            $this->adminDao->updateUserStatus($targetUserId, 'ACTIVE');
            $auditId = $this->auditLogDao->logAdminAction($adminId, 'UNLOCK_USER', $targetUserId, $cleanReason);

            if ($ownsTx) {
                $pdo->commit();
            }

            $updated = $this->adminDao->findUserById($targetUserId) ?? $target;
            return [
                'user' => $this->presentUser($updated),
                'auditLogId' => $auditId,
                'reason' => $cleanReason,
            ];
        } catch (\Throwable $e) {
            if ($ownsTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * List administrative audit logs (FR13.02, FR13.03, FR14.03, FR14.04).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAuditLogs(int $limit = 50, int $offset = 0): array
    {
        $rows = $this->auditLogDao->listLogs($limit, $offset);
        return array_map(static fn(array $r): array => [
            'id' => (int) $r['id'],
            'adminUserId' => (int) $r['admin_user_id'],
            'adminName' => (string) ($r['admin_name'] ?? 'Admin'),
            'adminEmail' => (string) ($r['admin_email'] ?? ''),
            'targetUserId' => $r['target_user_id'] !== null ? (int) $r['target_user_id'] : null,
            'targetName' => $r['target_name'] !== null ? (string) $r['target_name'] : null,
            'targetEmail' => $r['target_email'] !== null ? (string) $r['target_email'] : null,
            'action' => (string) $r['action'],
            'reason' => $r['reason'] !== null ? (string) $r['reason'] : null,
            'createdAt' => (string) $r['created_at'],
        ], $rows);
    }

    /**
     * Present user account strictly with macro metadata (no personal balances/transactions per BR11).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function presentUser(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'fullName' => (string) $row['full_name'],
            'email' => (string) $row['email'],
            'role' => (string) $row['role'],
            'status' => (string) $row['status'],
            'baseCurrency' => (string) $row['base_currency'],
            'createdAt' => (string) $row['created_at'],
            'updatedAt' => (string) ($row['updated_at'] ?? $row['created_at']),
        ];
    }
}
