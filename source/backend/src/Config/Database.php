<?php

declare(strict_types=1);

namespace WealthFlow\Config;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;
    private static string $driverUsed = 'none';

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_NAME') ?: 'wealthflow';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $driverPref = strtolower(getenv('DB_DRIVER') ?: 'mysql');

        if ($driverPref === 'mysql') {
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$instance = $pdo;
                self::$driverUsed = 'mysql';
                return self::$instance;
            } catch (PDOException $e) {
                // MySQL not running or database not created yet; fallback gracefully to SQLite
                error_log("WealthFlow: MySQL connection failed ({$e->getMessage()}). Falling back to SQLite.");
            }
        }

        // SQLite fallback (in data directory or memory)
        $sqliteDir = dirname(__DIR__, 3) . '/database';
        if (!is_dir($sqliteDir)) {
            @mkdir($sqliteDir, 0755, true);
        }
        $sqliteFile = $sqliteDir . '/wealthflow.sqlite';
        $pdo = new PDO('sqlite:' . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        
        self::$instance = $pdo;
        self::$driverUsed = 'sqlite';
        self::initSqliteSchema($pdo);

        return self::$instance;
    }

    public static function getDriver(): string
    {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$driverUsed;
    }

    public static function setConnection(PDO $pdo, string $driver = 'sqlite'): void
    {
        self::$instance = $pdo;
        self::$driverUsed = $driver;
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$driverUsed = 'none';
    }

    public static function initSqliteSchema(PDO $pdo): void
    {
        $schema = <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'USER',
            status TEXT NOT NULL DEFAULT 'ACTIVE',
            base_currency TEXT NOT NULL DEFAULT 'VND',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS wallets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            wallet_type TEXT NOT NULL,
            currency TEXT NOT NULL DEFAULT 'VND',
            cached_balance REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'ACTIVE',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE (user_id, name)
        );

        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            category_type TEXT NOT NULL,
            archived_at DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE (user_id, name, category_type)
        );

        CREATE TABLE IF NOT EXISTS default_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category_type TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            UNIQUE (name, category_type)
        );

        CREATE TABLE IF NOT EXISTS exchange_rates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            from_currency TEXT NOT NULL,
            to_currency TEXT NOT NULL,
            rate REAL NOT NULL,
            effective_at DATETIME NOT NULL,
            provider TEXT NULL
        );

        CREATE TABLE IF NOT EXISTS ledger_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            wallet_id INTEGER NULL,
            code TEXT NOT NULL,
            account_type TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (wallet_id) REFERENCES wallets(id),
            UNIQUE (user_id, code)
        );

        CREATE TABLE IF NOT EXISTS financial_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            category_id INTEGER NULL,
            transaction_type TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'DRAFT',
            description TEXT NOT NULL,
            currency TEXT NOT NULL,
            amount REAL NOT NULL,
            fx_rate_snapshot REAL NOT NULL DEFAULT 1,
            occurred_on DATE NOT NULL,
            posted_at DATETIME NULL,
            original_transaction_id INTEGER NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (category_id) REFERENCES categories(id),
            FOREIGN KEY (original_transaction_id) REFERENCES financial_transactions(id)
        );

        CREATE TABLE IF NOT EXISTS ledger_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER NOT NULL,
            ledger_account_id INTEGER NOT NULL,
            entry_side TEXT NOT NULL,
            amount_original REAL NOT NULL,
            currency TEXT NOT NULL,
            amount_base REAL NOT NULL,
            fx_rate_snapshot REAL NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id),
            FOREIGN KEY (ledger_account_id) REFERENCES ledger_accounts(id)
        );

        CREATE TABLE IF NOT EXISTS idempotency_keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            request_key TEXT NOT NULL,
            request_hash TEXT NOT NULL,
            transaction_id INTEGER NULL,
            result_code INTEGER NULL,
            response_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id),
            UNIQUE (user_id, request_key)
        );

        CREATE TABLE IF NOT EXISTS budgets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            budget_month DATE NOT NULL,
            limit_amount REAL NOT NULL,
            status TEXT NOT NULL DEFAULT 'ACTIVE',
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (category_id) REFERENCES categories(id),
            UNIQUE (user_id, category_id, budget_month)
        );

        CREATE TABLE IF NOT EXISTS recurring_schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            wallet_id INTEGER NOT NULL,
            category_id INTEGER NULL,
            name TEXT NOT NULL,
            amount REAL NOT NULL,
            currency TEXT NOT NULL,
            day_of_month INTEGER NOT NULL,
            next_due_date DATE NOT NULL,
            execution_mode TEXT NOT NULL DEFAULT 'DRAFT_REMINDER',
            status TEXT NOT NULL DEFAULT 'ACTIVE',
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (wallet_id) REFERENCES wallets(id),
            FOREIGN KEY (category_id) REFERENCES categories(id)
        );

        CREATE TABLE IF NOT EXISTS recurring_executions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recurring_schedule_id INTEGER NOT NULL,
            due_date DATE NOT NULL,
            transaction_id INTEGER NULL,
            status TEXT NOT NULL DEFAULT 'PENDING',
            FOREIGN KEY (recurring_schedule_id) REFERENCES recurring_schedules(id),
            FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id),
            UNIQUE (recurring_schedule_id, due_date)
        );

        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            body TEXT NOT NULL,
            notification_type TEXT NOT NULL,
            dedup_key TEXT NULL,
            read_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE (user_id, dedup_key)
        );
SQL;

        $pdo->exec($schema);
        self::seedDefaultData($pdo);
    }

    private static function seedDefaultData(PDO $pdo): void
    {
        // Seed default test user if empty
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO users (id, full_name, email, password_hash, role, status, base_currency) 
                VALUES (1, 'Võ Trịnh Quốc Huy', 'huy.user@wealthflow.local', '\$2y\$10\$abcdefghijklmnopqrstuvwxyzABCDEF', 'USER', 'ACTIVE', 'VND')");
        }

        // Seed default wallets for user 1 if empty
        $stmt = $pdo->query('SELECT COUNT(*) FROM wallets WHERE user_id = 1');
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO wallets (id, user_id, name, wallet_type, currency, cached_balance, status) VALUES 
                (1, 1, 'Ví tiền mặt', 'CASH', 'VND', 2500000, 'ACTIVE'),
                (2, 1, 'Tài khoản Vietcombank', 'BANK', 'VND', 18450000, 'ACTIVE'),
                (3, 1, 'Ví MoMo', 'E_WALLET', 'VND', 1200000, 'ACTIVE'),
                (4, 1, 'Ví ngoại tệ USD', 'BANK', 'USD', 200, 'ACTIVE')");
        }

        // Seed default ledger accounts for user 1
        $stmt = $pdo->query('SELECT COUNT(*) FROM ledger_accounts WHERE user_id = 1');
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO ledger_accounts (user_id, wallet_id, code, account_type) VALUES 
                (1, 1, 'ASSET_WALLET_1', 'ASSET'),
                (1, 2, 'ASSET_WALLET_2', 'ASSET'),
                (1, 3, 'ASSET_WALLET_3', 'ASSET'),
                (1, 4, 'ASSET_WALLET_4', 'ASSET'),
                (1, null, 'EQUITY_OPENING', 'EQUITY'),
                (1, null, 'INCOME_DEFAULT', 'INCOME'),
                (1, null, 'EXPENSE_DEFAULT', 'EXPENSE'),
                (1, null, 'FX_GAIN_LOSS', 'EXPENSE')");
        }

        // Seed categories for user 1
        $stmt = $pdo->query('SELECT COUNT(*) FROM categories WHERE user_id = 1');
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO categories (id, user_id, name, category_type) VALUES 
                (1, 1, 'Ăn uống', 'EXPENSE'),
                (2, 1, 'Di chuyển & Xăng xe', 'EXPENSE'),
                (3, 1, 'Tiền thuê nhà & Điện nước', 'EXPENSE'),
                (4, 1, 'Mua sắm cá nhân', 'EXPENSE'),
                (5, 1, 'Giải trí & Cafe', 'EXPENSE'),
                (6, 1, 'Lương cố định', 'INCOME'),
                (7, 1, 'Thu nhập Freelance', 'INCOME'),
                (8, 1, 'Thưởng & Thu nhập khác', 'INCOME')");
        }

        // Seed exchange rates
        $stmt = $pdo->query('SELECT COUNT(*) FROM exchange_rates');
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_at, provider) VALUES 
                ('VND', 'VND', 1.0, CURRENT_TIMESTAMP, 'SYSTEM'),
                ('USD', 'VND', 25400.0, CURRENT_TIMESTAMP, 'VIETCOMBANK'),
                ('EUR', 'VND', 27600.0, CURRENT_TIMESTAMP, 'VIETCOMBANK')");
        }
    }
}
