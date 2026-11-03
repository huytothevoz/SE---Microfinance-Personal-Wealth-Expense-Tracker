<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Auth;

use PDO;
use WealthFlow\Core\Database;

final class UserDAO
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM users WHERE email = ?';
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createUser(string $name, string $email, string $passwordHash, string $baseCurrency): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, base_currency) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([trim($name), strtolower(trim($email)), $passwordHash, strtoupper($baseCurrency)]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $stmt->execute([$passwordHash, $userId]);
    }

    public function updateStatus(int $userId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        return $stmt->execute([strtoupper($status), $userId]);
    }

    public function closeAccount(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET status = 'LOCKED', closed_at = CURRENT_TIMESTAMP
             WHERE id = ? AND status = 'ACTIVE' AND closed_at IS NULL"
        );
        $stmt->execute([$userId]);
        return $stmt->rowCount() === 1;
    }

    public function copyDefaultCategories(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (user_id, name, category_type)
             SELECT ?, name, category_type FROM default_categories WHERE is_active = 1'
        );
        $stmt->execute([$userId]);
    }

    public function queueRegistrationEmail(int $userId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO registration_emails (user_id) VALUES (?)');
        $stmt->execute([$userId]);
    }

    public function createSession(string $sessionId, int $userId, string $refreshHash, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auth_sessions (id, user_id, refresh_hash, expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$sessionId, $userId, $refreshHash, $expiresAt]);
    }

    /** @return array<string, mixed>|null */
    public function findActiveSession(string $sessionId, int $userId): ?array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = $driver === 'sqlite' ? "datetime('now')" : 'UTC_TIMESTAMP()';
        $stmt = $this->pdo->prepare(
            "SELECT u.*, s.id AS session_id, s.refresh_hash, s.expires_at
             FROM auth_sessions s
             JOIN users u ON u.id = s.user_id
             WHERE s.id = ? AND u.id = ? AND s.expires_at > {$nowExpr} AND u.status = 'ACTIVE'"
        );
        $stmt->execute([$sessionId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateSessionRefresh(string $sessionId, string $refreshHash): bool
    {
        $stmt = $this->pdo->prepare('UPDATE auth_sessions SET refresh_hash = ? WHERE id = ?');
        return $stmt->execute([$refreshHash, $sessionId]);
    }

    public function deleteSession(string $sessionId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM auth_sessions WHERE id = ?');
        return $stmt->execute([$sessionId]);
    }

    public function deleteSessionsByUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM auth_sessions WHERE user_id = ?');
        return $stmt->execute([$userId]);
    }

    public function recordSecurityAudit(string $event, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO security_audit_logs (user_id, event) VALUES (?, ?)'
        );
        $stmt->execute([$userId, $event]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listUsers(?int $limit = 50, ?int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, role, status, base_currency, created_at
             FROM users ORDER BY id ASC LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
