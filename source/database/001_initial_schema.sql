-- WealthFlow / Construction UI Prototype / MySQL 8.0+ / InnoDB
-- SCHEMA DRAFT: baseline for later backend integration. NOT production-ready.
-- Add ledger integrity tests, immutable posting constraints, migrations, RBAC, and JWT revocation before real usage.
CREATE DATABASE IF NOT EXISTS wealthflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wealthflow;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('USER','ADMIN') NOT NULL DEFAULT 'USER',
  status ENUM('ACTIVE','LOCKED') NOT NULL DEFAULT 'ACTIVE',
  base_currency CHAR(3) NOT NULL DEFAULT 'VND',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  wallet_type ENUM('CASH','BANK','E_WALLET','SAVINGS','CREDIT') NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  cached_balance DECIMAL(24,8) NOT NULL DEFAULT 0,
  status ENUM('ACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_wallet_user_name (user_id, name),
  KEY idx_wallet_user_status (user_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS default_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category_type ENUM('INCOME','EXPENSE') NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  UNIQUE KEY uk_default_category (name, category_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  category_type ENUM('INCOME','EXPENSE') NOT NULL,
  archived_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uk_user_category (user_id, name, category_type),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exchange_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_currency CHAR(3) NOT NULL,
  to_currency CHAR(3) NOT NULL,
  rate DECIMAL(24,10) NOT NULL,
  effective_at DATETIME NOT NULL,
  provider VARCHAR(100) NULL,
  CHECK (rate > 0),
  KEY idx_rate_pair_date (from_currency, to_currency, effective_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ledger_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  wallet_id BIGINT UNSIGNED NULL,
  code VARCHAR(90) NOT NULL,
  account_type ENUM('ASSET','LIABILITY','EQUITY','INCOME','EXPENSE') NOT NULL,
  UNIQUE KEY uk_ledger_account (user_id,code),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (wallet_id) REFERENCES wallets(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS financial_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  transaction_type ENUM('INCOME','EXPENSE','TRANSFER','LOAN_DISBURSEMENT','LOAN_PAYMENT','GOLD_PURCHASE','OTHER') NOT NULL,
  status ENUM('DRAFT','POSTED','REVERSED') NOT NULL DEFAULT 'DRAFT',
  description VARCHAR(255) NOT NULL,
  currency CHAR(3) NOT NULL,
  amount DECIMAL(24,8) NOT NULL,
  fx_rate_snapshot DECIMAL(24,10) NOT NULL DEFAULT 1,
  occurred_on DATE NOT NULL,
  posted_at DATETIME NULL,
  original_transaction_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (amount > 0),
  KEY idx_tx_user_date (user_id, occurred_on),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (original_transaction_id) REFERENCES financial_transactions(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ledger_entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaction_id BIGINT UNSIGNED NOT NULL,
  ledger_account_id BIGINT UNSIGNED NOT NULL,
  entry_side ENUM('DEBIT','CREDIT') NOT NULL,
  amount_original DECIMAL(24,8) NOT NULL,
  currency CHAR(3) NOT NULL,
  amount_base DECIMAL(24,8) NOT NULL,
  fx_rate_snapshot DECIMAL(24,10) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (amount_original > 0),
  CHECK (amount_base > 0),
  KEY idx_ledger_tx (transaction_id),
  FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id),
  FOREIGN KEY (ledger_account_id) REFERENCES ledger_accounts(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS idempotency_keys (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  request_key CHAR(36) NOT NULL,
  request_hash CHAR(64) NOT NULL,
  transaction_id BIGINT UNSIGNED NULL,
  result_code SMALLINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_idempotency (user_id, request_key),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS budgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  budget_month DATE NOT NULL,
  limit_amount DECIMAL(24,8) NOT NULL,
  status ENUM('ACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  CHECK (limit_amount > 0),
  UNIQUE KEY uk_budget_user_category_month (user_id, category_id, budget_month),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recurring_schedules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  wallet_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  amount DECIMAL(24,8) NOT NULL,
  currency CHAR(3) NOT NULL,
  day_of_month TINYINT UNSIGNED NOT NULL,
  next_due_date DATE NOT NULL,
  execution_mode ENUM('DRAFT_REMINDER','AUTO_POST') NOT NULL DEFAULT 'DRAFT_REMINDER',
  status ENUM('ACTIVE','PAUSED') NOT NULL DEFAULT 'ACTIVE',
  CHECK (amount > 0),
  CHECK (day_of_month BETWEEN 1 AND 31),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (wallet_id) REFERENCES wallets(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recurring_executions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recurring_schedule_id BIGINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  transaction_id BIGINT UNSIGNED NULL,
  status ENUM('PENDING','COMPLETED','FAILED') NOT NULL DEFAULT 'PENDING',
  UNIQUE KEY uk_recurring_once (recurring_schedule_id, due_date),
  FOREIGN KEY (recurring_schedule_id) REFERENCES recurring_schedules(id),
  FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS financial_goals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  target_amount DECIMAL(24,8) NOT NULL,
  deadline DATE NOT NULL,
  status ENUM('ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  CHECK (target_amount > 0),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS goal_allocations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(24,8) NOT NULL,
  allocated_on DATE NOT NULL,
  note VARCHAR(250) NULL,
  CHECK (amount > 0),
  FOREIGN KEY (goal_id) REFERENCES financial_goals(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gold_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  gold_type ENUM('RING_9999','SJC_BAR') NOT NULL,
  quantity_chi DECIMAL(18,4) NOT NULL,
  valuation_per_chi DECIMAL(24,8) NOT NULL,
  valuation_date DATE NOT NULL,
  status ENUM('HELD','LIQUIDATED') NOT NULL DEFAULT 'HELD',
  CHECK (quantity_chi >= 0),
  CHECK (valuation_per_chi >= 0),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS micro_loans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  partner_name VARCHAR(150) NOT NULL,
  loan_direction ENUM('BORROWED','LENT') NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  principal_original DECIMAL(24,8) NOT NULL,
  principal_remaining DECIMAL(24,8) NOT NULL,
  interest_description VARCHAR(150) NULL,
  next_due_date DATE NULL,
  status ENUM('ACTIVE','PAID_OFF','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  CHECK (principal_original > 0),
  CHECK (principal_remaining >= 0),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS loan_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_id BIGINT UNSIGNED NOT NULL,
  transaction_id BIGINT UNSIGNED NULL,
  principal_amount DECIMAL(24,8) NOT NULL,
  interest_fee_amount DECIMAL(24,8) NOT NULL DEFAULT 0,
  paid_on DATE NOT NULL,
  CHECK (principal_amount >= 0),
  CHECK (interest_fee_amount >= 0),
  FOREIGN KEY (loan_id) REFERENCES micro_loans(id),
  FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS net_worth_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  total_assets DECIMAL(24,8) NOT NULL,
  total_liabilities DECIMAL(24,8) NOT NULL,
  net_worth DECIMAL(24,8) NOT NULL,
  UNIQUE KEY uk_snapshot_day (user_id,snapshot_date),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(170) NOT NULL,
  body TEXT NOT NULL,
  notification_type ENUM('BUDGET','LOAN','RECURRING','BROADCAST','SYSTEM') NOT NULL,
  dedup_key VARCHAR(150) NULL,
  read_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_notification_dedup (user_id,dedup_key),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_config (
  config_key VARCHAR(100) PRIMARY KEY,
  config_value VARCHAR(500) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(80) NOT NULL,
  target_user_id BIGINT UNSIGNED NULL,
  reason VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_user_id) REFERENCES users(id),
  FOREIGN KEY (target_user_id) REFERENCES users(id)
) ENGINE=InnoDB;


-- Pending: refresh-token rotation/revocation schema, category owner constraints across joins,
-- referential user isolation, strict immutable ledger posting, base-currency reconciliation,
-- cross-currency weighted cost-basis accounting, complete reversal lifecycle and migrations.
-- All MUST be finalized and tested before writing real financial data.
