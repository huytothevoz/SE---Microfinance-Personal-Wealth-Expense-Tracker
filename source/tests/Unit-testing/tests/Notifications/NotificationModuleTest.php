<?php

declare(strict_types=1);

namespace WealthFlowTest\Notifications;

use WealthFlow\Modules\Notifications\NotificationDAO;
use WealthFlow\Modules\Notifications\NotificationService;
use WealthFlowTest\Support\DatabaseTestCase;

final class NotificationModuleTest extends DatabaseTestCase
{
    private NotificationDAO $dao;
    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->insertUser(2);
        $this->insertUser(3, 'LOCKED');
        $this->dao = new NotificationDAO($this->pdo);
        $this->service = new NotificationService($this->dao, $this->pdo);
    }

    public function testInjectedDependenciesAreReturned(): void
    {
        self::assertSame($this->pdo, $this->dao->getPdo());
        self::assertSame($this->dao, $this->service->getDao());
    }

    public function testCreateFindCountAndDeduplicateNotification(): void
    {
        self::assertTrue($this->dao->create(2, 'Tin', 'Nội dung', 'budget', 'key-1', 'warning'));
        self::assertFalse($this->dao->create(2, 'Tin lặp', 'Nội dung', 'budget', 'key-1'));

        $rows = $this->dao->findAllByUser(2);
        self::assertCount(1, $rows);
        self::assertSame('BUDGET', $rows[0]['notification_type']);
        self::assertSame('WARNING', $rows[0]['priority']);
        self::assertSame(1, $this->dao->countUnreadByUser(2));
        self::assertSame((int) $rows[0]['id'], (int) $this->dao->findByIdAndUser((int) $rows[0]['id'], 2)['id']);
        self::assertNull($this->dao->findByIdAndUser((int) $rows[0]['id'], 3));
    }

    public function testInvalidPriorityFallsBackToInfo(): void
    {
        $this->dao->create(2, 'Tin', 'Nội dung', 'SYSTEM', null, 'invalid');
        self::assertSame('INFO', $this->dao->findAllByUser(2)[0]['priority']);
    }

    public function testMarkAsReadIsScopedAndIdempotent(): void
    {
        $this->dao->create(2, 'Tin', 'Nội dung', 'SYSTEM');
        $id = (int) $this->dao->findAllByUser(2)[0]['id'];

        self::assertFalse($this->dao->markAsRead($id, 3));
        self::assertTrue($this->dao->markAsRead($id, 2));
        self::assertTrue($this->dao->markAsRead($id, 2));
        self::assertSame(0, $this->dao->countUnreadByUser(2));
        self::assertCount(0, $this->dao->findAllByUser(2, 30, true));
    }

    public function testMarkAllAsReadUpdatesOnlySelectedUser(): void
    {
        $this->dao->create(1, 'A', 'A', 'SYSTEM');
        $this->dao->create(2, 'B', 'B', 'SYSTEM');
        $this->dao->create(2, 'C', 'C', 'SYSTEM');

        self::assertTrue($this->dao->markAllAsRead(2));
        self::assertSame(0, $this->dao->countUnreadByUser(2));
        self::assertSame(1, $this->dao->countUnreadByUser(1));
    }

    public function testBroadcastTargetsOnlyActiveUsersAndNormalizesPriority(): void
    {
        $count = $this->dao->broadcast('Bảo trì', '01:00', 'bad-priority');

        self::assertSame(2, $count);
        self::assertSame('INFO', $this->dao->findAllByUser(2)[0]['priority']);
        self::assertCount(0, $this->dao->findAllByUser(3));
    }

    public function testServiceDelegatesEveryOperation(): void
    {
        self::assertTrue($this->service->notify(2, 'Tin', 'Nội dung', 'system', 'service-key', 'URGENT'));
        self::assertSame(1, $this->service->countUnreadForUser(2));
        $rows = $this->service->getNotificationsForUser(2, 10, true);
        self::assertCount(1, $rows);
        self::assertTrue($this->service->markAsRead((int) $rows[0]['id'], 2));
        self::assertTrue($this->service->markAllAsRead(2));
        self::assertSame(2, $this->service->broadcast('Toàn hệ thống', 'Nội dung', 'CRITICAL'));
    }
}
