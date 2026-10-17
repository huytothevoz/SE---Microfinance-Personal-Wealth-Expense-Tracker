-- Table 20: gold_transactions (Physical Gold Buy/Sell/Revaluation Trade Log)
CREATE TABLE IF NOT EXISTS gold_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  gold_asset_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  transaction_type ENUM('BUY','SELL','REVALUATION') NOT NULL,
  quantity_chi DECIMAL(18,4) NOT NULL,
  unit_price DECIMAL(24,8) NOT NULL,
  total_amount DECIMAL(24,8) NOT NULL,
  wallet_id BIGINT UNSIGNED NULL,
  occurred_on DATE NOT NULL,
  KEY idx_gold_tx_asset (gold_asset_id, occurred_on),
  FOREIGN KEY (gold_asset_id) REFERENCES gold_assets(id) ON DELETE RESTRICT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE SET NULL
) ENGINE=InnoDB;
