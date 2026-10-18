-- Table 06: wallets (Asset, Liability & Cash Accounts)
CREATE TABLE IF NOT EXISTS wallets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  wallet_type ENUM('CASH','BANK','E_WALLET','SAVINGS','CREDIT') NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  cached_balance DECIMAL(24,8) NOT NULL DEFAULT 0,
  earmarked_balance DECIMAL(24,8) NOT NULL DEFAULT 0,
  status ENUM('ACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_wallet_user_name (user_id, name),
  KEY idx_wallet_user_status (user_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
