<?php

declare(strict_types=1);

namespace WealthFlowTest\Support;

use PDO;
use PHPUnit\Framework\TestCase;
use WealthFlow\Core\Database;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new CompatibleSqlitePDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        Database::initSqliteSchema($this->pdo);
        Database::setConnection($this->pdo, 'sqlite');
    }

    protected function tearDown(): void
    {
        Database::reset();
        parent::tearDown();
    }

    protected function insertUser(
        int $id,
        string $status = 'ACTIVE',
        string $role = 'USER',
        string $currency = 'VND'
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT OR REPLACE INTO users
             (id, full_name, email, password_hash, role, status, base_currency)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $id,
            "Test User {$id}",
            "user{$id}@wealthflow.test",
            'hash',
            $role,
            $status,
            $currency,
        ]);
    }
}

final class CompatibleSqlitePDO extends PDO
{
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->sqliteCreateFunction('UTC_TIMESTAMP', static fn(): string => gmdate('Y-m-d H:i:s'));
    }

    private static function rewrite(string $sql): string
    {
        $sql = preg_replace('/\s+FOR\s+UPDATE\b/i', '', $sql) ?? $sql;
        if (stripos($sql, 'ON DUPLICATE KEY UPDATE') !== false) {
            $sql = preg_replace(
                '/INSERT\s+INTO\s+auth_rate_limits\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)\s*ON\s+DUPLICATE\s+KEY\s+UPDATE\s+attempts\s*=\s*attempts\s*\+\s*1/i',
                'INSERT INTO auth_rate_limits ($1) VALUES ($2) ON CONFLICT(bucket) DO UPDATE SET attempts = attempts + 1',
                $sql
            ) ?? $sql;
        }
        return $sql;
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        return parent::prepare(self::rewrite($query), $options);
    }

    public function exec(string $statement): int|false
    {
        return parent::exec(self::rewrite($statement));
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        $query = self::rewrite($query);
        return $fetchMode === null
            ? parent::query($query)
            : parent::query($query, $fetchMode, ...$fetchModeArgs);
    }
}
