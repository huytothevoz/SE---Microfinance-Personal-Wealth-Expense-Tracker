-- Table 11: ledger_entries (Double-Entry Balanced Journal Entries)
CREATE TABLE IF NOT EXISTS ledger_entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaction_id BIGINT UNSIGNED NOT NULL,
  ledger_account_id BIGINT UNSIGNED NOT NULL,
  entry_side ENUM('DEBIT','CREDIT') NOT NULL,
  amount_original DECIMAL(24,8) NOT NULL,
  currency CHAR(3) NOT NULL,
  amount_base DECIMAL(24,8) NOT NULL,
  fx_rate_snapshot DECIMAL(24,10) NOT NULL DEFAULT 1.0000000000,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (amount_original > 0),
  CHECK (amount_base > 0),
  KEY idx_ledger_tx (transaction_id),
  FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id) ON DELETE RESTRICT,
  FOREIGN KEY (ledger_account_id) REFERENCES ledger_accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
