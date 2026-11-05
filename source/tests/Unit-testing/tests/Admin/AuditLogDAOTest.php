<?php

declare(strict_types=1);

namespace WealthFlowTest\Admin;

use WealthFlowTest\Support\AdminTestCase;

final class AuditLogDAOTest extends AdminTestCase
{
    public function testGetPdoReturnsInjectedConnection(): void
    {
        self::assertSame($this->pdo, $this->auditLogDao->getPdo());
    }

    public function testLogSecurityEventNormalizesAndReturnsId(): void
    {
        $id = $this->auditLogDao->logSecurityEvent(2, ' login_failed ');
        $row = $this->pdo->query("SELECT * FROM security_audit_logs WHERE id = {$id}")->fetch();

        self::assertGreaterThan(0, $id);
        self::assertSame('LOGIN_FAILED', $row['event']);
        self::assertSame(2, (int) $row['user_id']);
    }

    public function testLogExportAuditNormalizesTypeAndClampsNegativeRowCount(): void
    {
        $id = $this->auditLogDao->logExportAudit(2, ' transactions_csv ', -5, 'sha256', '127.0.0.1');
        $row = $this->pdo->query("SELECT * FROM export_audit_logs WHERE id = {$id}")->fetch();

        self::assertSame('TRANSACTIONS_CSV', $row['export_type']);
        self::assertSame(0, (int) $row['row_count']);
        self::assertSame('sha256', $row['file_hash']);
        self::assertSame('127.0.0.1', $row['ip_address']);
    }

    public function testGetExportLogsByUserFiltersOrdersAndLimits(): void
    {
        $this->auditLogDao->logExportAudit(2, 'FIRST', 1);
        $this->auditLogDao->logExportAudit(2, 'SECOND', 2);
        $this->auditLogDao->logExportAudit(1, 'ADMIN_EXPORT', 3);

        $rows = $this->auditLogDao->getExportLogsByUser(2, 1);

        self::assertCount(1, $rows);
        self::assertSame('SECOND', $rows[0]['export_type']);
    }

    public function testGetSecurityLogsByUserFiltersOrdersAndLimits(): void
    {
        $this->auditLogDao->logSecurityEvent(2, 'FIRST');
        $this->auditLogDao->logSecurityEvent(2, 'SECOND');
        $this->auditLogDao->logSecurityEvent(1, 'ADMIN_EVENT');

        $rows = $this->auditLogDao->getSecurityLogsByUser(2, 1);

        self::assertCount(1, $rows);
        self::assertSame('SECOND', $rows[0]['event']);
    }

    public function testLogAdminActionNormalizesAndPersistsAllValues(): void
    {
        $id = $this->auditLogDao->logAdminAction(1, ' lock_user ', 2, 'Vi phạm điều khoản');
        $row = $this->pdo->query("SELECT * FROM admin_audit_logs WHERE id = {$id}")->fetch();

        self::assertGreaterThan(0, $id);
        self::assertSame('LOCK_USER', $row['action']);
        self::assertSame(2, (int) $row['target_user_id']);
        self::assertSame('Vi phạm điều khoản', $row['reason']);
    }

    public function testGetAdminLogsIncludesAdminEmailAndOrdersNewestFirst(): void
    {
        $this->auditLogDao->logAdminAction(1, 'FIRST');
        $this->auditLogDao->logAdminAction(1, 'SECOND');

        $rows = $this->auditLogDao->getAdminLogs(1);

        self::assertCount(1, $rows);
        self::assertSame('SECOND', $rows[0]['action']);
        self::assertSame('admin@wealthflow.test', $rows[0]['admin_email']);
    }

    public function testGetBroadcastLogsReturnsOnlyBroadcastActions(): void
    {
        $this->auditLogDao->logAdminAction(1, 'UPDATE_SYSTEM_CONFIG');
        $this->auditLogDao->logAdminAction(1, 'BROADCAST_ANNOUNCEMENT', null, 'Tin thứ nhất');
        $this->auditLogDao->logAdminAction(1, 'BROADCAST_ANNOUNCEMENT', null, 'Tin thứ hai');

        $rows = $this->auditLogDao->getBroadcastLogs(1);

        self::assertCount(1, $rows);
        self::assertSame('Tin thứ hai', $rows[0]['reason']);
        self::assertSame('admin@wealthflow.test', $rows[0]['admin_email']);
    }
}
