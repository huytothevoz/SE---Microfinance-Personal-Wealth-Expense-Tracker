-- Table 10: financial_transactions (Core Financial Transaction Header)
CREATE TABLE IF NOT EXISTS financial_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  transaction_type ENUM('INCOME','EXPENSE','TRANSFER','LOAN_DISBURSEMENT','LOAN_PAYMENT','GOLD_PURCHASE','GOLD_SALE','OTHER') NOT NULL,
  status ENUM('DRAFT','POSTED','REVERSED') NOT NULL DEFAULT 'DRAFT',
  description VARCHAR(255) NOT NULL,
  currency CHAR(3) NOT NULL,
  amount DECIMAL(24,8) NOT NULL,
  fx_rate_snapshot DECIMAL(24,10) NOT NULL DEFAULT 1.0000000000,
  occurred_on DATE NOT NULL,
  posted_at DATETIME NULL,
  original_transaction_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (amount > 0),
  KEY idx_tx_user_date (user_id, occurred_on),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  FOREIGN KEY (original_transaction_id) REFERENCES financial_transactions(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
