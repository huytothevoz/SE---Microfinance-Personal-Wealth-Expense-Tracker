-- Table 09: ledger_accounts (Double-Entry Chart of Accounts)
CREATE TABLE IF NOT EXISTS ledger_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  wallet_id BIGINT UNSIGNED NULL,
  code VARCHAR(90) NOT NULL,
  account_type ENUM('ASSET','LIABILITY','EQUITY','INCOME','EXPENSE') NOT NULL,
  UNIQUE KEY uk_ledger_account (user_id, code),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
