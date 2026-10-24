<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Admin;

use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Notifications\NotificationDAO;
use WealthFlow\Modules\Notifications\NotificationService;

final class AdminService
{
    /**
     * System operational parameter rules and valid bounds (FR13.02, EF13.02).
     *
     * @var array<string, array{
     *     type: string,
     *     min?: int,
     *     max?: int,
     *     allowed?: array<int, string>,
     *     default: string,
     *     label: string,
     *     description: string
     * }>
     */
    private const CONFIG_SPECS = [
        'BUDGET_WARNING_THRESHOLD' => [
            'type' => 'int',
            'min' => 50,
            'max' => 95,
            'default' => '80',
            'label' => 'Ngưỡng cảnh báo ngân sách sớm (%)',
            'description' => 'Tỷ lệ phần trăm chi tiêu kích hoạt cảnh báo màu vàng (50% - 95%).',
        ],
        'BUDGET_CRITICAL_THRESHOLD' => [
            'type' => 'int',
            'min' => 95,
            'max' => 200,
            'default' => '100',
            'label' => 'Ngưỡng vượt ngân sách (%)',
            'description' => 'Tỷ lệ phần trăm chi tiêu kích hoạt cảnh báo đỏ vượt hạn mức (95% - 200%).',
        ],
        'ACCESS_TOKEN_TTL_MINUTES' => [
            'type' => 'int',
            'min' => 5,
            'max' => 60,
            'default' => '15',
            'label' => 'Thời hạn Access Token (phút)',
            'description' => 'Thời gian hiệu lực của Access JWT ngắn hạn (5 - 60 phút).',
        ],
        'SESSION_EXPIRY_DAYS' => [
            'type' => 'int',
            'min' => 1,
            'max' => 30,
            'default' => '7',
            'label' => 'Thời hạn phiên đăng nhập (ngày)',
            'description' => 'Thời gian hiệu lực của Refresh Token (1 - 30 ngày).',
        ],
        'CSV_EXPORT_MAX_ROWS' => [
            'type' => 'int',
            'min' => 100,
            'max' => 10000,
            'default' => '10000',
            'label' => 'Giới hạn dòng xuất CSV tối đa',
            'description' => 'Số bản ghi tối đa cho mỗi lần xuất báo cáo CSV (100 - 10.000 dòng).',
        ],
        'MAX_FAILED_LOGINS' => [
            'type' => 'int',
            'min' => 3,
            'max' => 20,
            'default' => '5',
            'label' => 'Số lần đăng nhập sai tối đa',
            'description' => 'Giới hạn số lần thử mật khẩu sai liên tiếp trước khi tạm chặn (3 - 20 lần).',
        ],
        'OTP_EXPIRY_MINUTES' => [
            'type' => 'int',
            'min' => 3,
            'max' => 30,
            'default' => '10',
            'label' => 'Thời hạn mã OTP (phút)',
            'description' => 'Thời gian hiệu lực của mã xác thực OTP khôi phục mật khẩu (3 - 30 phút).',
        ],
        'LOAN_REMINDER_DAYS' => [
            'type' => 'int',
            'min' => 1,
            'max' => 14,
            'default' => '3',
            'label' => 'Nhắc lịch trả nợ trước (ngày)',
            'description' => 'Số ngày gửi thông báo nhắc hạn thanh toán khoản vay trước kỳ hạn (1 - 14 ngày).',
        ],
        'DEFAULT_BASE_CURRENCY' => [
            'type' => 'enum',
            'allowed' => ['VND', 'USD', 'EUR'],
            'default' => 'VND',
            'label' => 'Tiền tệ cơ sở mặc định',
            'description' => 'Đơn vị tiền tệ cơ sở mặc định khi đăng ký tài khoản mới (VND, USD, EUR).',
        ],
        'ALLOW_REGISTRATION' => [
            'type' => 'bool',
            'allowed' => ['true', 'false'],
            'default' => 'true',
            'label' => 'Cho phép đăng ký mới',
            'description' => 'Bật/tắt cho phép người dùng mới tự đăng ký tài khoản trên hệ thống.',
        ],
    ];

    private AdminDAO $adminDao;
    private AuditLogDAO $auditDao;
    private NotificationService $notificationService;

    public function __construct(
        ?AdminDAO $adminDao = null,
        ?AuditLogDAO $auditDao = null,
        ?NotificationService $notificationService = null
    ) {
        $this->adminDao = $adminDao ?? new AdminDAO();
        $pdo = $this->adminDao->getPdo();
        $this->auditDao = $auditDao ?? new AuditLogDAO($pdo);
        $this->notificationService = $notificationService ?? new NotificationService(new NotificationDAO($pdo));
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
     * Lock a user account, immediately revoke all active sessions, and record audit log (FR14.03, EF12.01, EF12.02, SQ08).
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
            $auditId = $this->auditDao->logAction($adminId, 'LOCK_USER', $targetUserId, $cleanReason);

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
        } catch (Throwable $e) {
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
            $auditId = $this->auditDao->logAction($adminId, 'UNLOCK_USER', $targetUserId, $cleanReason);

            if ($ownsTx) {
                $pdo->commit();
            }

            $updated = $this->adminDao->findUserById($targetUserId) ?? $target;
            return [
                'user' => $this->presentUser($updated),
                'auditLogId' => $auditId,
                'reason' => $cleanReason,
            ];
        } catch (Throwable $e) {
            if ($ownsTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * List default system categories (FR13.01).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listDefaultCategories(?string $type = null, bool $includeInactive = true): array
    {
        $rows = $this->adminDao->listDefaultCategories($type, $includeInactive);
        return array_map(static fn(array $r): array => [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'categoryType' => (string) $r['category_type'],
            'type' => (string) $r['category_type'],
            'isActive' => (int) $r['is_active'] === 1,
        ], $rows);
    }

    /**
     * Create a new system default category (FR13.01).
     * Duplicate (name, category_type) throws 409 Conflict.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createDefaultCategory(int $adminId, array $input): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($input['name'] ?? '')) ?? '');
        $type = strtoupper(trim((string) ($input['categoryType'] ?? $input['type'] ?? 'EXPENSE')));
        $isActive = !isset($input['isActive']) || filter_var($input['isActive'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false;

        if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
            throw new ApiException(
                'VALIDATION_ERROR',
                'Tên danh mục mặc định phải từ 1 đến 120 ký tự.',
                422,
                ['name' => 'Vui lòng nhập tên danh mục hợp lệ (1 - 120 ký tự).']
            );
        }
        if (!in_array($type, ['INCOME', 'EXPENSE'], true)) {
            throw new ApiException(
                'VALIDATION_ERROR',
                'Loại danh mục phải là INCOME hoặc EXPENSE.',
                422,
                ['categoryType' => 'Chọn INCOME hoặc EXPENSE.']
            );
        }

        if ($this->adminDao->findDefaultCategoryByNameAndType($name, $type) !== null) {
            throw new ApiException(
                'DUPLICATE_DEFAULT_CATEGORY',
                'Danh mục hệ thống mặc định đã tồn tại.',
                409,
                ['name' => 'Tên danh mục đã tồn tại trong cùng nhóm thu/chi.']
            );
        }

        $id = $this->adminDao->createDefaultCategory($name, $type, $isActive);
        $this->auditDao->logAction(
            $adminId,
            'CREATE_DEFAULT_CATEGORY',
            null,
            "Tạo danh mục mặc định [{$type}] {$name}"
        );

        return [
            'id' => $id,
            'name' => $name,
            'categoryType' => $type,
            'type' => $type,
            'isActive' => $isActive,
        ];
    }

    /**
     * Update or toggle active status of a system default category (FR13.01).
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateDefaultCategory(int $adminId, int $id, array $input): array
    {
        $existing = $this->adminDao->findDefaultCategoryById($id);
        if ($existing === null) {
            throw new ApiException('NOT_FOUND', 'Không tìm thấy danh mục mặc định.', 404);
        }

        $name = isset($input['name'])
            ? trim(preg_replace('/\s+/u', ' ', (string) $input['name']) ?? '')
            : (string) $existing['name'];
        $type = isset($input['categoryType']) || isset($input['type'])
            ? strtoupper(trim((string) ($input['categoryType'] ?? $input['type'])))
            : (string) $existing['category_type'];
        $isActive = array_key_exists('isActive', $input)
            ? (bool) filter_var($input['isActive'], FILTER_VALIDATE_BOOLEAN)
            : ((int) $existing['is_active'] === 1);

        if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
            throw new ApiException(
                'VALIDATION_ERROR',
                'Tên danh mục mặc định phải từ 1 đến 120 ký tự.',
                422
            );
        }
        if (!in_array($type, ['INCOME', 'EXPENSE'], true)) {
            throw new ApiException(
                'VALIDATION_ERROR',
                'Loại danh mục phải là INCOME hoặc EXPENSE.',
                422
            );
        }

        if ($this->adminDao->findDefaultCategoryByNameAndType($name, $type, $id) !== null) {
            throw new ApiException(
                'DUPLICATE_DEFAULT_CATEGORY',
                'Danh mục hệ thống mặc định đã tồn tại.',
                409
            );
        }

        $this->adminDao->updateDefaultCategory($id, $name, $type, $isActive);
        $this->auditDao->logAction(
            $adminId,
            'UPDATE_DEFAULT_CATEGORY',
            null,
            "Cập nhật danh mục mặc định #{$id}: [{$type}] {$name} (active=" . ($isActive ? '1' : '0') . ')'
        );

        return [
            'id' => $id,
            'name' => $name,
            'categoryType' => $type,
            'type' => $type,
            'isActive' => $isActive,
        ];
    }

    /**
     * Get all system configuration parameters with metadata and current values (FR13.02).
     *
     * @return array<string, mixed>
     */
    public function getSystemConfig(): array
    {
        $rows = $this->adminDao->listSystemConfigs();
        $dbMap = [];
        $updatedAtMap = [];
        foreach ($rows as $row) {
            $key = strtoupper((string) $row['config_key']);
            $dbMap[$key] = (string) $row['config_value'];
            $updatedAtMap[$key] = (string) $row['updated_at'];
        }

        $items = [];
        $values = [];
        foreach (self::CONFIG_SPECS as $key => $spec) {
            $val = $dbMap[$key] ?? $spec['default'];
            $values[$key] = $val;
            $items[] = [
                'key' => $key,
                'value' => $val,
                'default' => $spec['default'],
                'type' => $spec['type'],
                'min' => $spec['min'] ?? null,
                'max' => $spec['max'] ?? null,
                'allowed' => $spec['allowed'] ?? null,
                'label' => $spec['label'],
                'description' => $spec['description'],
                'updatedAt' => $updatedAtMap[$key] ?? null,
            ];
        }

        // Include any extra custom keys stored in DB
        foreach ($dbMap as $key => $val) {
            if (!isset(self::CONFIG_SPECS[$key])) {
                $values[$key] = $val;
                $items[] = [
                    'key' => $key,
                    'value' => $val,
                    'default' => $val,
                    'type' => 'string',
                    'min' => null,
                    'max' => null,
                    'allowed' => null,
                    'label' => $key,
                    'description' => 'Tham số cấu hình bổ sung.',
                    'updatedAt' => $updatedAtMap[$key] ?? null,
                ];
            }
        }

        return [
            'configs' => $items,
            'configMap' => $values,
        ];
    }

    /**
     * Validate and update one or multiple system configuration parameters (FR13.02, EF13.02).
     * Out-of-range values are rejected with 422 Unprocessable Entity.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateSystemConfig(int $adminId, array $input, ?string $singleKey = null): array
    {
        $updates = [];

        if ($singleKey !== null && trim($singleKey) !== '') {
            $key = strtoupper(trim($singleKey));
            $val = $input['value'] ?? $input['configValue'] ?? $input[$key] ?? null;
            if ($val === null) {
                throw new ApiException('VALIDATION_ERROR', "Thiếu giá trị cấu hình cho tham số {$key}.", 422);
            }
            $updates[$key] = (string) $val;
        } elseif (isset($input['configs']) && is_array($input['configs'])) {
            foreach ($input['configs'] as $k => $v) {
                if (is_array($v) && isset($v['key'])) {
                    $updates[strtoupper(trim((string) $v['key']))] = (string) ($v['value'] ?? '');
                } else {
                    $updates[strtoupper(trim((string) $k))] = is_bool($v) ? ($v ? 'true' : 'false') : (string) $v;
                }
            }
        } elseif (isset($input['key']) && array_key_exists('value', $input)) {
            $updates[strtoupper(trim((string) $input['key']))] = is_bool($input['value'])
                ? ($input['value'] ? 'true' : 'false')
                : (string) $input['value'];
        } else {
            foreach ($input as $k => $v) {
                if (is_scalar($v)) {
                    $updates[strtoupper(trim((string) $k))] = is_bool($v) ? ($v ? 'true' : 'false') : (string) $v;
                }
            }
        }

        if ($updates === []) {
            throw new ApiException('VALIDATION_ERROR', 'Không có tham số cấu hình nào được gửi lên.', 422);
        }

        $current = $this->getSystemConfig()['configMap'];
        $normalizedUpdates = [];

        foreach ($updates as $key => $rawValue) {
            if (!isset(self::CONFIG_SPECS[$key])) {
                throw new ApiException(
                    'UNKNOWN_CONFIG_KEY',
                    "Tham số cấu hình '{$key}' không tồn tại hoặc không được phép chỉnh sửa.",
                    422,
                    [$key => 'Tham số cấu hình không hợp lệ.']
                );
            }

            $spec = self::CONFIG_SPECS[$key];
            $val = trim($rawValue);

            if ($spec['type'] === 'int') {
                if (!preg_match('/^-?\d+$/D', $val)) {
                    throw new ApiException(
                        'INVALID_CONFIG_VALUE',
                        "Giá trị của '{$key}' phải là số nguyên hợp lệ.",
                        422,
                        [$key => 'Phải là số nguyên.']
                    );
                }
                $intVal = (int) $val;
                $min = $spec['min'] ?? PHP_INT_MIN;
                $max = $spec['max'] ?? PHP_INT_MAX;
                if ($intVal < $min || $intVal > $max) {
                    throw new ApiException(
                        'INVALID_CONFIG_VALUE',
                        "Giá trị của '{$key}' ({$intVal}) nằm ngoài dải hợp lệ [{$min} .. {$max}].",
                        422,
                        [$key => "Giá trị phải nằm trong khoảng từ {$min} đến {$max}."]
                    );
                }
                $normalizedUpdates[$key] = (string) $intVal;
            } elseif ($spec['type'] === 'enum') {
                $upper = strtoupper($val);
                $allowed = $spec['allowed'] ?? [];
                if (!in_array($upper, $allowed, true)) {
                    throw new ApiException(
                        'INVALID_CONFIG_VALUE',
                        "Giá trị của '{$key}' chỉ chấp nhận: " . implode(', ', $allowed) . '.',
                        422
                    );
                }
                $normalizedUpdates[$key] = $upper;
            } elseif ($spec['type'] === 'bool') {
                $lower = strtolower($val);
                if (in_array($lower, ['1', 'true', 'yes', 'on'], true)) {
                    $normalizedUpdates[$key] = 'true';
                } elseif (in_array($lower, ['0', 'false', 'no', 'off'], true)) {
                    $normalizedUpdates[$key] = 'false';
                } else {
                    throw new ApiException(
                        'INVALID_CONFIG_VALUE',
                        "Giá trị của '{$key}' phải là true hoặc false.",
                        422
                    );
                }
            }
        }

        // Cross-check budget warning < critical threshold
        $effectiveWarning = (int) ($normalizedUpdates['BUDGET_WARNING_THRESHOLD'] ?? $current['BUDGET_WARNING_THRESHOLD'] ?? 80);
        $effectiveCritical = (int) ($normalizedUpdates['BUDGET_CRITICAL_THRESHOLD'] ?? $current['BUDGET_CRITICAL_THRESHOLD'] ?? 100);
        if ($effectiveWarning >= $effectiveCritical) {
            throw new ApiException(
                'INVALID_CONFIG_VALUE',
                'Ngưỡng cảnh báo sớm (BUDGET_WARNING_THRESHOLD) phải nhỏ hơn ngưỡng vượt ngân sách (BUDGET_CRITICAL_THRESHOLD).',
                422
            );
        }

        $pdo = $this->adminDao->getPdo();
        $ownsTx = !$pdo->inTransaction();
        if ($ownsTx) {
            $pdo->beginTransaction();
        }

        try {
            $changeSummary = [];
            foreach ($normalizedUpdates as $key => $value) {
                $oldVal = $current[$key] ?? '';
                $this->adminDao->upsertSystemConfig($key, $value);
                $changeSummary[] = "{$key}: {$oldVal} -> {$value}";
            }

            $this->auditDao->logAction(
                $adminId,
                'UPDATE_SYSTEM_CONFIG',
                null,
                implode('; ', $changeSummary)
            );

            if ($ownsTx) {
                $pdo->commit();
            }

            $latest = $this->getSystemConfig();
            $latest['updatedKeys'] = array_keys($normalizedUpdates);
            return $latest;
        } catch (Throwable $e) {
            if ($ownsTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Broadcast a system announcement to all ACTIVE users via SQL Batch Fan-out (FR13.03, EF13.01, SQ08).
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function broadcastAnnouncement(int $adminId, array $input): array
    {
        $title = trim(preg_replace('/\s+/u', ' ', (string) ($input['title'] ?? '')) ?? '');
        $body = trim((string) ($input['body'] ?? $input['content'] ?? $input['message'] ?? ''));
        $rawPriority = strtoupper(trim((string) ($input['priority'] ?? 'INFO')));

        // EF13.01: Empty title or body -> 400 Bad Request
        if ($title === '' || $body === '') {
            throw new ApiException(
                'BROADCAST_EMPTY_CONTENT',
                'Tiêu đề và nội dung thông báo phát thanh không được để trống.',
                400,
                [
                    'title' => $title === '' ? 'Vui lòng nhập tiêu đề thông báo.' : '',
                    'body' => $body === '' ? 'Vui lòng nhập nội dung thông báo.' : '',
                ]
            );
        }

        if (mb_strlen($title, 'UTF-8') > 150) {
            throw new ApiException(
                'VALIDATION_ERROR',
                'Tiêu đề thông báo không được vượt quá 150 ký tự.',
                422,
                ['title' => 'Tối đa 150 ký tự.']
            );
        }

        if (mb_strlen($body, 'UTF-8') > 2000) {
            throw new ApiException(
                'VALIDATION_ERROR',
                'Nội dung thông báo không được vượt quá 2.000 ký tự.',
                422,
                ['body' => 'Tối đa 2.000 ký tự.']
            );
        }

        $allowedPriorities = ['INFO', 'WARNING', 'URGENT', 'CRITICAL', 'MAINTENANCE'];
        if (!in_array($rawPriority, $allowedPriorities, true)) {
            throw new ApiException(
                'VALIDATION_ERROR',
                'Mức độ ưu tiên không hợp lệ (chỉ chấp nhận INFO, WARNING, URGENT, CRITICAL, MAINTENANCE).',
                422
            );
        }

        // Map URGENT -> CRITICAL for notifications table enum compatibility while keeping display priority
        $dbPriority = $rawPriority === 'URGENT' ? 'CRITICAL' : $rawPriority;

        $recipientCount = $this->notificationService->broadcast($title, $body, $dbPriority);

        $auditPayload = json_encode([
            'title' => $title,
            'body' => $body,
            'priority' => $rawPriority,
            'recipientCount' => $recipientCount,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $auditId = $this->auditDao->logAction($adminId, 'BROADCAST_ANNOUNCEMENT', null, $auditPayload);

        return [
            'id' => $auditId,
            'title' => $title,
            'body' => $body,
            'priority' => $rawPriority,
            'dbPriority' => $dbPriority,
            'recipientCount' => $recipientCount,
            'sentAt' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * List broadcast history (FR13.03).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listAnnouncements(int $limit = 30): array
    {
        return $this->adminDao->listBroadcastHistory($limit);
    }

    /**
     * List administrative audit logs (FR13.02, FR13.03, FR14.03, FR14.04).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAuditLogs(int $limit = 50, int $offset = 0): array
    {
        $rows = $this->auditDao->listLogs($limit, $offset);
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
