<?php

declare(strict_types=1);

namespace WealthFlowTest\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use WealthFlowTest\Support\AdminTestCase;

final class AdminServiceTest extends AdminTestCase
{
    public function testGetDefaultCategoriesReturnsMappedCategories(): void
    {
        $categories = $this->service->getDefaultCategories(1);

        self::assertCount(3, $categories);
        self::assertSame(['id', 'name', 'categoryType', 'isActive'], array_keys($categories[0]));
    }

    public function testEveryAdminFunctionRejectsNonAdminUser(): void
    {
        $calls = [
            fn() => $this->service->getDefaultCategories(2),
            fn() => $this->service->createDefaultCategory(2, ['name' => 'Mới', 'categoryType' => 'INCOME']),
            fn() => $this->service->updateDefaultCategory(2, 1, ['name' => 'Mới']),
            fn() => $this->service->deleteDefaultCategory(2, 1),
            fn() => $this->service->getSystemConfigs(2),
            fn() => $this->service->updateSystemConfigs(2, ['CSV_EXPORT_MAX_ROWS' => 1000]),
            fn() => $this->service->broadcastAnnouncement(2, ['title' => 'Tin', 'body' => 'Nội dung']),
            fn() => $this->service->getBroadcastHistory(2),
        ];

        foreach ($calls as $call) {
            $this->assertApiException($call, 'FORBIDDEN', 403);
        }
    }

    public function testCreateDefaultCategoryNormalizesAndPersistsValues(): void
    {
        $created = $this->service->createDefaultCategory(1, [
            'name' => '  Thưởng dự án  ',
            'type' => ' income ',
            'isActive' => false,
        ]);

        self::assertSame('Thưởng dự án', $created['name']);
        self::assertSame('INCOME', $created['categoryType']);
        self::assertFalse($created['isActive']);
        self::assertSame('CREATE_DEFAULT_CATEGORY', $this->latestAdminAction());
    }

    public static function invalidCreateCategoryCases(): array
    {
        return [
            'blank name' => [['name' => ' ', 'categoryType' => 'INCOME'], 'name'],
            'name over 100 chars' => [['name' => str_repeat('A', 101), 'categoryType' => 'INCOME'], 'name'],
            'invalid type' => [['name' => 'Danh mục', 'categoryType' => 'OTHER'], 'categoryType'],
        ];
    }

    #[DataProvider('invalidCreateCategoryCases')]
    public function testCreateDefaultCategoryRejectsInvalidInput(array $dto, string $field): void
    {
        $this->assertApiException(
            fn() => $this->service->createDefaultCategory(1, $dto),
            'VALIDATION_ERROR',
            422,
            $field
        );
    }

    public function testCreateDefaultCategoryRejectsDuplicate(): void
    {
        $this->assertApiException(
            fn() => $this->service->createDefaultCategory(1, [
                'name' => 'Ăn uống',
                'categoryType' => 'EXPENSE',
            ]),
            'CATEGORY_ALREADY_EXISTS',
            409
        );
    }

    public function testUpdateDefaultCategoryChangesOnlyProvidedFields(): void
    {
        $updated = $this->service->updateDefaultCategory(1, 2, [
            'name' => '  Ăn uống gia đình ',
            'isActive' => false,
        ]);

        self::assertSame('Ăn uống gia đình', $updated['name']);
        self::assertSame('EXPENSE', $updated['categoryType']);
        self::assertFalse($updated['isActive']);
        self::assertSame('UPDATE_DEFAULT_CATEGORY', $this->latestAdminAction());
    }

    public function testUpdateDefaultCategoryRejectsMissingCategory(): void
    {
        $this->assertApiException(
            fn() => $this->service->updateDefaultCategory(1, 999, ['name' => 'Mới']),
            'NOT_FOUND',
            404
        );
    }

    public static function invalidUpdateCategoryCases(): array
    {
        return [
            'blank name' => [['name' => ''], 'name'],
            'long name' => [['name' => str_repeat('Đ', 101)], 'name'],
            'invalid type' => [['categoryType' => 'UNKNOWN'], 'categoryType'],
        ];
    }

    #[DataProvider('invalidUpdateCategoryCases')]
    public function testUpdateDefaultCategoryRejectsInvalidFields(array $dto, string $field): void
    {
        $this->assertApiException(
            fn() => $this->service->updateDefaultCategory(1, 2, $dto),
            'VALIDATION_ERROR',
            422,
            $field
        );
    }

    public function testUpdateDefaultCategoryRejectsDuplicateCombination(): void
    {
        $this->assertApiException(
            fn() => $this->service->updateDefaultCategory(1, 2, [
                'name' => 'Lương',
                'categoryType' => 'INCOME',
            ]),
            'CATEGORY_ALREADY_EXISTS',
            409
        );
    }

    public function testDeleteDefaultCategoryDeletesAndAudits(): void
    {
        self::assertTrue($this->service->deleteDefaultCategory(1, 3));
        self::assertNull($this->adminDao->findDefaultCategoryById(3));
        self::assertSame('DELETE_DEFAULT_CATEGORY', $this->latestAdminAction());
    }

    public function testDeleteDefaultCategoryRejectsMissingCategory(): void
    {
        $this->assertApiException(
            fn() => $this->service->deleteDefaultCategory(1, 999),
            'NOT_FOUND',
            404
        );
    }

    public function testGetSystemConfigsMergesDefaultsCurrentValuesDefinitionsAndLogs(): void
    {
        $this->adminDao->updateSystemConfig('BUDGET_WARNING_THRESHOLD_PERCENT', '85');
        $this->auditLogDao->logAdminAction(1, 'TEST_ACTION');

        $result = $this->service->getSystemConfigs(1);

        self::assertSame('85', $result['configs']['BUDGET_WARNING_THRESHOLD_PERCENT']);
        self::assertSame('60', $result['configs']['JWT_ACCESS_TOKEN_TTL_MINUTES']);
        self::assertSame(['label', 'type', 'min', 'max', 'unit', 'description'],
            array_keys($result['definitions']['CSV_EXPORT_MAX_ROWS']));
        self::assertNotEmpty($result['auditLogs']);
    }

    public function testUpdateSystemConfigsPersistsAllowedKeysAndCreatesAuditLog(): void
    {
        $result = $this->service->updateSystemConfigs(1, [
            'BUDGET_WARNING_THRESHOLD_PERCENT' => 90,
            'JWT_ACCESS_TOKEN_TTL_MINUTES' => '120',
            'CSV_EXPORT_MAX_ROWS' => 10000,
            'LOAN_REMINDER_DAYS_BEFORE' => 7,
            'IGNORED_KEY' => 'ignored',
        ]);

        self::assertSame('90', $result['configs']['BUDGET_WARNING_THRESHOLD_PERCENT']);
        self::assertSame('120', $result['configs']['JWT_ACCESS_TOKEN_TTL_MINUTES']);
        self::assertSame('10000', $result['configs']['CSV_EXPORT_MAX_ROWS']);
        self::assertSame('7', $result['configs']['LOAN_REMINDER_DAYS_BEFORE']);
        self::assertArrayNotHasKey('IGNORED_KEY', $result['configs']);
        self::assertSame('UPDATE_SYSTEM_CONFIG', $this->latestAdminAction());
    }

    public static function invalidSystemConfigCases(): array
    {
        return [
            'budget below min' => [['BUDGET_WARNING_THRESHOLD_PERCENT' => 49], 'BUDGET_WARNING_THRESHOLD_PERCENT'],
            'budget above max' => [['BUDGET_WARNING_THRESHOLD_PERCENT' => 96], 'BUDGET_WARNING_THRESHOLD_PERCENT'],
            'token not numeric' => [['JWT_ACCESS_TOKEN_TTL_MINUTES' => 'abc'], 'JWT_ACCESS_TOKEN_TTL_MINUTES'],
            'token above max' => [['JWT_ACCESS_TOKEN_TTL_MINUTES' => 1441], 'JWT_ACCESS_TOKEN_TTL_MINUTES'],
            'CSV below min' => [['CSV_EXPORT_MAX_ROWS' => 99], 'CSV_EXPORT_MAX_ROWS'],
            'CSV above max' => [['CSV_EXPORT_MAX_ROWS' => 50001], 'CSV_EXPORT_MAX_ROWS'],
            'reminder below min' => [['LOAN_REMINDER_DAYS_BEFORE' => 0], 'LOAN_REMINDER_DAYS_BEFORE'],
            'reminder above max' => [['LOAN_REMINDER_DAYS_BEFORE' => 31], 'LOAN_REMINDER_DAYS_BEFORE'],
        ];
    }

    #[DataProvider('invalidSystemConfigCases')]
    public function testUpdateSystemConfigsRejectsOutOfRangeValues(array $dto, string $field): void
    {
        $this->assertApiException(
            fn() => $this->service->updateSystemConfigs(1, $dto),
            'VALIDATION_ERROR',
            422,
            $field
        );
    }

    public function testUpdateSystemConfigsRejectsPayloadWithoutAllowedKeys(): void
    {
        $this->assertApiException(
            fn() => $this->service->updateSystemConfigs(1, ['UNKNOWN' => 1]),
            'VALIDATION_ERROR',
            422
        );
    }

    public function testBroadcastAnnouncementNormalizesPriorityAndTargetsActiveUsers(): void
    {
        $result = $this->service->broadcastAnnouncement(1, [
            'title' => '  Thông báo bảo trì  ',
            'body' => '  Hệ thống bảo trì lúc 01:00.  ',
            'priority' => 'not-supported',
        ]);

        self::assertSame('Thông báo bảo trì', $result['title']);
        self::assertSame('Hệ thống bảo trì lúc 01:00.', $result['body']);
        self::assertSame('INFO', $result['priority']);
        self::assertSame(2, $result['recipientCount']);
        self::assertSame('DISPATCHED', $result['status']);
        self::assertSame(0, $this->notificationDao->countUnreadByUser(3));
        self::assertSame('BROADCAST_ANNOUNCEMENT', $this->latestAdminAction());
    }

    public static function invalidBroadcastCases(): array
    {
        return [
            'blank title' => [['title' => ' ', 'body' => 'Nội dung'], 'title'],
            'long title' => [['title' => str_repeat('A', 256), 'body' => 'Nội dung'], 'title'],
            'blank body' => [['title' => 'Tin', 'body' => ' '], 'body'],
            'long body' => [['title' => 'Tin', 'body' => str_repeat('B', 2001)], 'body'],
        ];
    }

    #[DataProvider('invalidBroadcastCases')]
    public function testBroadcastAnnouncementRejectsInvalidContent(array $dto, string $field): void
    {
        $this->assertApiException(
            fn() => $this->service->broadcastAnnouncement(1, $dto),
            'VALIDATION_ERROR',
            422,
            $field
        );
    }

    public function testGetBroadcastHistoryReturnsOnlyBroadcastLogs(): void
    {
        $this->auditLogDao->logAdminAction(1, 'UPDATE_SYSTEM_CONFIG');
        $this->auditLogDao->logAdminAction(1, 'BROADCAST_ANNOUNCEMENT', null, 'Tin 1');
        $this->auditLogDao->logAdminAction(1, 'BROADCAST_ANNOUNCEMENT', null, 'Tin 2');

        $history = $this->service->getBroadcastHistory(1, 1);

        self::assertCount(1, $history);
        self::assertSame('BROADCAST_ANNOUNCEMENT', $history[0]['action']);
        self::assertSame('Tin 2', $history[0]['reason']);
    }

    private function latestAdminAction(): string
    {
        $logs = $this->auditLogDao->getAdminLogs(1);
        self::assertNotEmpty($logs);
        return (string) $logs[0]['action'];
    }
}
