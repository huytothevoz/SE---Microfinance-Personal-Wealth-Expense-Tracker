<?php

declare(strict_types=1);

namespace WealthFlow\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $instance = null;
    private static string $driverUsed = 'none';

    public static function connect(Config $config, bool $selectDatabase = true): PDO
    {
        $name = $config->get('DB_NAME');
        if (!preg_match('/^[A-Za-z0-9_]+$/D', $name)) {
            throw new RuntimeException('Invalid database name.');
        }
        $dsn = 'mysql:host=' . $config->get('DB_HOST') . ';port=' . $config->get('DB_PORT')
            . ($selectDatabase ? ';dbname=' . $name : '') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $config->get('DB_USER'), $config->get('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    }

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = require dirname(__DIR__, 2) . '/bootstrap.php';
        self::$instance = self::connect($config);
        self::$driverUsed = (string) self::$instance->getAttribute(PDO::ATTR_DRIVER_NAME);

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
            active_name_key TEXT NULL,
            wallet_type TEXT NOT NULL,
            currency TEXT NOT NULL DEFAULT 'VND',
            cached_balance REAL NOT NULL DEFAULT 0,
            earmarked_balance REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'ACTIVE',
            icon_key TEXT NULL,
            color_key TEXT NULL,
            archived_at DATETIME NULL,
            deleted_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE (user_id, active_name_key)
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
            rate TEXT NOT NULL,
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
            spent_amount REAL NOT NULL DEFAULT 0,
            alert_level TEXT NOT NULL DEFAULT 'NORMAL',
            is_yellow_sent INTEGER NOT NULL DEFAULT 0,
            is_red_sent INTEGER NOT NULL DEFAULT 0,
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

        CREATE TABLE IF NOT EXISTS micro_loans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            wallet_id INTEGER NULL,
            title TEXT NOT NULL,
            partner_name TEXT NOT NULL,
            loan_direction TEXT NOT NULL,
            currency TEXT NOT NULL DEFAULT 'VND',
            principal_original REAL NOT NULL,
            principal_remaining REAL NOT NULL,
            interest_rate_year REAL NOT NULL DEFAULT 0,
            term_months INTEGER NOT NULL DEFAULT 0,
            start_date DATE NOT NULL,
            next_due_date DATE NULL,
            status TEXT NOT NULL DEFAULT 'ACTIVE',
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (wallet_id) REFERENCES wallets(id)
        );

        CREATE TABLE IF NOT EXISTS loan_repayment_schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            loan_id INTEGER NOT NULL,
            period_number INTEGER NOT NULL,
            due_date DATE NOT NULL,
            principal_due REAL NOT NULL DEFAULT 0,
            interest_due REAL NOT NULL DEFAULT 0,
            principal_paid REAL NOT NULL DEFAULT 0,
            interest_paid REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'PENDING',
            FOREIGN KEY (loan_id) REFERENCES micro_loans(id)
        );

        CREATE TABLE IF NOT EXISTS loan_payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            loan_id INTEGER NOT NULL,
            transaction_id INTEGER NULL,
            principal_amount REAL NOT NULL,
            interest_fee_amount REAL NOT NULL DEFAULT 0,
            paid_on DATE NOT NULL,
            FOREIGN KEY (loan_id) REFERENCES micro_loans(id),
            FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id)
        );

        CREATE TABLE IF NOT EXISTS gold_assets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            gold_type TEXT NOT NULL,
            quantity_chi REAL NOT NULL,
            total_cost_basis REAL NOT NULL DEFAULT 0,
            weighted_avg_cost REAL NOT NULL DEFAULT 0,
            valuation_per_chi REAL NOT NULL,
            valuation_date DATE NOT NULL,
            status TEXT NOT NULL DEFAULT 'HELD',
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS gold_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            gold_asset_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            transaction_type TEXT NOT NULL,
            quantity_chi REAL NOT NULL,
            unit_price REAL NOT NULL,
            total_amount REAL NOT NULL,
            wallet_id INTEGER NULL,
            occurred_on DATE NOT NULL,
            FOREIGN KEY (gold_asset_id) REFERENCES gold_assets(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (wallet_id) REFERENCES wallets(id)
        );

        CREATE TABLE IF NOT EXISTS financial_goals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            wallet_id INTEGER NULL,
            name TEXT NOT NULL,
            target_amount REAL NOT NULL,
            current_amount REAL NOT NULL DEFAULT 0,
            deadline DATE NOT NULL,
            priority TEXT NOT NULL DEFAULT 'MEDIUM',
            status TEXT NOT NULL DEFAULT 'ACTIVE',
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (wallet_id) REFERENCES wallets(id)
        );

        CREATE TABLE IF NOT EXISTS goal_allocations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            goal_id INTEGER NOT NULL,
            wallet_id INTEGER NULL,
            allocation_type TEXT NOT NULL DEFAULT 'DEPOSIT',
            amount REAL NOT NULL,
            allocated_on DATE NOT NULL,
            note TEXT NULL,
            FOREIGN KEY (goal_id) REFERENCES financial_goals(id),
            FOREIGN KEY (wallet_id) REFERENCES wallets(id)
        );

        CREATE TABLE IF NOT EXISTS net_worth_snapshots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            snapshot_date DATE NOT NULL,
            total_assets REAL NOT NULL,
            total_liabilities REAL NOT NULL,
            net_worth REAL NOT NULL,
            breakdown_json TEXT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE (user_id, snapshot_date)
        );

        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            body TEXT NOT NULL,
            notification_type TEXT NOT NULL,
            priority TEXT NOT NULL DEFAULT 'INFO',
            dedup_key TEXT NULL,
            read_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE (user_id, dedup_key)
        );

        CREATE TABLE IF NOT EXISTS security_audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            event TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS export_audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            export_type TEXT NOT NULL,
            row_count INTEGER NOT NULL DEFAULT 0,
            file_hash TEXT NULL,
            ip_address TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
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
            $pdo->exec("INSERT INTO wallets
                (id, user_id, name, active_name_key, wallet_type, currency, cached_balance, status) VALUES
                (1, 1, 'Ví tiền mặt', 'ví tiền mặt', 'CASH', 'VND', 2500000, 'ACTIVE'),
                (2, 1, 'Tài khoản Vietcombank', 'tài khoản vietcombank', 'BANK', 'VND', 18450000, 'ACTIVE'),
                (3, 1, 'Ví MoMo', 'ví momo', 'E_WALLET', 'VND', 1200000, 'ACTIVE'),
                (4, 1, 'Ví ngoại tệ USD', 'ví ngoại tệ usd', 'BANK', 'USD', 200, 'ACTIVE')");
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
